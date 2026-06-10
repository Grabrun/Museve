<?php
// 后台 API 公共认证
require_once __DIR__ . '/../../includes/db.php';

/**
 * 验证后台登录状态，返回用户信息或终止请求
 */
function requireAdmin(): array {
    $pdo = getDB();
    $token = $_COOKIE['cookie_token'] ?? '';

    if (empty($token)) {
        jsonResponse(401, '请先登录');
    }

    $stmt = $pdo->prepare("SELECT id, username, nickname, role FROM users WHERE cookie_token = :token AND token_expires > NOW() LIMIT 1");
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        setcookie('cookie_token', '', time() - 3600, '/');
        jsonResponse(401, '登录已过期，请重新登录');
    }

    return $user;
}
