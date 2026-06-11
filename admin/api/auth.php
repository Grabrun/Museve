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
    $token = $_COOKIE['museve_token'] ?? '';
    if ($token) {
        $stmt = $db->prepare("UPDATE users SET cookie_token = '', token_expires = NULL WHERE cookie_token = ?");
        $stmt->execute([$token]);
    }
    writeLog('logout', 'user', $_SESSION['admin_id'] ?? 0, '退出登录');
    setcookie('museve_token', '', time() - 3600, '/', '', false, true);
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    jsonResponse(200, '已退出登录');
}

// 登录
if ($method === 'POST' && $action === '') {
    $body = getJsonBody();
    $account = trim($body['account'] ?? '');
    $password = $body['password'] ?? '';

    if (empty($account) || empty($password)) {
        jsonResponse(400, '请输入账号和密码');
    }

    // 检查登录锁定
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (checkLoginLock($ip)) {
        jsonResponse(429, '登录尝试过多，请 30 分钟后再试');
    }

    $stmt = $db->prepare("SELECT * FROM users WHERE account = ? LIMIT 1");
    $stmt->execute([$account]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        recordLoginAttempt($ip, $account);
        jsonResponse(2000, '账号或密码错误');
    }

    // 清除登录失败记录
    clearLoginAttempts($ip);

    // 生成 token，30 分钟滑动过期
    $token = bin2hex(random_bytes(32));
    $stmt = $db->prepare("UPDATE users SET cookie_token = ?, token_expires = DATE_ADD(NOW(), INTERVAL 30 MINUTE), last_login = NOW() WHERE id = ?");
    $stmt->execute([$token, $user['id']]);

    setcookie('museve_token', $token, [
        'expires' => time() + 86400,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    // 生成 CSRF Token
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    writeLog('login', 'user', $user['id'], "登录成功: {$user['account']}");
    jsonResponse(200, '登录成功', [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ]);
}

jsonResponse(405, '方法不允许');
