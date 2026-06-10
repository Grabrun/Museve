<?php
// 后台认证 API
require_once __DIR__ . '/../../includes/db.php';

$pdo = getDB();
$method = getMethod();

// 解析路由: /admin/api/auth 或 /admin/api/auth/logout
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action = '';
if (preg_match('/admin\/api\/auth\/(\w+)/', $path, $m)) {
    $action = $m[1];
}

if ($action === 'logout' && $method === 'POST') {
    setcookie('cookie_token', '', time() - 3600, '/', '', false, true);
    jsonSuccess(['message' => '已退出登录']);
}

if ($method === 'POST' && $action === '') {
    $body = getJsonBody();
    $username = trim($body['username'] ?? '');
    $password = $body['password'] ?? '';

    if (empty($username) || empty($password)) {
        jsonResponse(ERR_INVALID_CREDENTIALS, '请输入账号和密码');
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(ERR_INVALID_CREDENTIALS, '账号或密码错误');
    }

    if (!empty($user['locked']) && $user['locked']) {
        jsonResponse(ERR_ACCOUNT_LOCKED, '账号已被锁定');
    }

    if (!password_verify($password, $user['password'])) {
        jsonResponse(ERR_INVALID_CREDENTIALS, '账号或密码错误');
    }

    // 生成 token 并写入 cookie
    $token = bin2hex(random_bytes(32));
    $expires = time() + 86400 * 7; // 7天

    $updateStmt = $pdo->prepare("UPDATE users SET cookie_token = :token, token_expires = :expires WHERE id = :id");
    $updateStmt->execute([
        ':token' => $token,
        ':expires' => date('Y-m-d H:i:s', $expires),
        ':id' => $user['id'],
    ]);

    setcookie('cookie_token', $token, $expires, '/', '', false, true);

    jsonSuccess([
        'id' => $user['id'],
        'username' => $user['username'],
        'nickname' => $user['nickname'] ?? '',
        'role' => $user['role'] ?? 'user',
    ]);
}

jsonResponse(405, 'Method Not Allowed');
