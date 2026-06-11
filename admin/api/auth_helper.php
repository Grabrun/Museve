<?php
// 暮想 Museve 后台认证助手
require_once __DIR__ . '/../../includes/connect.php';

// ============================================================
// 方法覆盖: POST 请求支持 _method=PUT/DELETE → 绕过 WAF 405
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 优先检查 URL 编码的 _method (DELETE 使用)
    $override = $_POST['_method'] ?? null;
    // 再检查 JSON body 中的 _method (PUT 使用)
    if (!$override) {
        $rawBody = file_get_contents('php://input');
        $body = json_decode($rawBody, true);
        $override = $body['_method'] ?? null;
    }
    if ($override && in_array(strtoupper($override), ['PUT', 'DELETE'])) {
        $_SERVER['REQUEST_METHOD'] = strtoupper($override);
    }
}

/**
 * 验证后台登录状态，返回用户信息或终止请求
 */
function requireAuth(): array {
    $db = getDB();
    $token = $_COOKIE['museve_token'] ?? '';

    if (empty($token)) {
        jsonResponse(401, '请先登录');
    }

    $stmt = $db->prepare("SELECT id, account, username, role FROM users WHERE cookie_token = ? AND token_expires > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        setcookie('museve_token', '', time() - 3600, '/', '', false, true);
        jsonResponse(401, '登录已过期，请重新登录');
    }

    // 滑动过期：延长 30 分钟
    $stmt = $db->prepare("UPDATE users SET token_expires = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE id = ?");
    $stmt->execute([$user['id']]);

    return $user;
}

/**
 * 生成 CSRF Token
 */
function generateCsrfToken(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 验证 CSRF Token
 */
function verifyCsrfToken(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['_csrf'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        jsonResponse(403, 'CSRF Token 无效');
    }
}

/**
 * 检查登录失败锁定
 */
function checkLoginLock(string $ip): bool {
    $db = getDB();
    // 确保表存在（首次使用前创建）
    $db->exec("
        CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `ip` VARCHAR(45) NOT NULL,
            `account` VARCHAR(50) NOT NULL,
            `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ip_time` (`ip`, `attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $stmt->execute([$ip]);
    return $stmt->fetchColumn() >= 5;
}

/**
 * 记录登录失败
 */
function recordLoginAttempt(string $ip, string $account): void {
    $db = getDB();
    // 创建登录尝试表（如果不存在）
    $db->exec("
        CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `ip` VARCHAR(45) NOT NULL,
            `account` VARCHAR(50) NOT NULL,
            `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ip_time` (`ip`, `attempted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    $stmt = $db->prepare("INSERT INTO `login_attempts` (`ip`, `account`) VALUES (?, ?)");
    $stmt->execute([$ip, $account]);
}

/**
 * 清除登录失败记录
 */
function clearLoginAttempts(string $ip): void {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip = ?");
    $stmt->execute([$ip]);
}
