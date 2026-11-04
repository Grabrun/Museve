<?php
// 暮想 Museve 后台认证 API
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/auth_helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// 解析路由: /admin/api/auth 或 /admin/api/auth/logout
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = '';
if (preg_match('/admin\/api\/auth\/(\w+)/', $path, $m)) {
    $action = $m[1];
}

// 退出登录
if ($action === 'logout' && $method === 'POST') {
    // 登出同样是状态变更：不校验 CSRF 的话，任意站点都能把访客踢下线
    verifyCsrfToken();

    $token = $_COOKIE['museve_token'] ?? '';
    if ($token) {
        $stmt = $db->prepare("UPDATE users SET cookie_token = '', token_expires = NULL WHERE cookie_token = ?");
        $stmt->execute([$token]);
    }
    writeLog('logout', 'user', $_SESSION['admin_id'] ?? 0, '退出登录');

    clearAuthCookie();

    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }
    jsonResponse(200, '已退出登录');
}

// 登录
if ($method === 'POST' && $action === '') {
    $body = getJsonBody();
    $account = trim((string)($body['account'] ?? ''));
    $password = (string)($body['password'] ?? '');

    if ($account === '' || $password === '') {
        jsonResponse(400, '请输入账号和密码');
    }

    // 过长的账号直接拒绝，避免拿它去撞 VARCHAR(50)
    if (mb_strlen($account) > 50) {
        jsonResponse(400, '账号或密码错误');
    }

    // 检查登录锁定（IP + 账号双键，见 auth_helper.php）
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (checkLoginLock($ip, $account)) {
        $minutes = max(1, (int)securityConfig('login_lockout_minutes', 30));
        jsonResponse(429, "登录尝试过多，请 {$minutes} 分钟后再试");
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE account = ? LIMIT 1");
    $stmt->execute([$account]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        recordLoginAttempt($ip, $account);
        jsonResponse(2000, '账号或密码错误');
    }

    // 登录成功才清空该 IP / 账号的失败记录
    clearLoginAttempts($ip, $account);

    // 重新生成会话 ID：防会话固定（攻击者预先塞一个已知 PHPSESSID 给受害者）
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    session_regenerate_id(true);

    // 生成 token，有效期取后台设置
    $mins = tokenExpiryMinutes();
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("UPDATE users SET cookie_token = ?, token_expires = DATE_ADD(NOW(), INTERVAL {$mins} MINUTE), last_login = NOW() WHERE id = ?");
    $stmt->execute([$token, $user['id']]);

    setAuthCookie($token);

    // 提权后换一张新的 CSRF 票据
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['admin_id']   = (int)$user['id'];
    $_SESSION['admin_role'] = $user['role'];

    writeLog('login', 'user', $user['id'], "登录成功: {$user['account']}");
    jsonResponse(200, '登录成功', [
        'id'       => $user['id'],
        'username' => $user['username'],
        'role'     => $user['role'],
    ]);
}

jsonResponse(405, '方法不允许');
