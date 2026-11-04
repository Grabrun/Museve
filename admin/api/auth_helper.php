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
        $body = getJsonBody();
        $override = $body['_method'] ?? null;
    }
    if ($override && in_array(strtoupper((string)$override), ['PUT', 'DELETE'])) {
        $_SERVER['REQUEST_METHOD'] = strtoupper((string)$override);
    }
}

/**
 * 读取安全配置项
 *
 * config.php 的 security 块此前全库无人引用，改配置等于白改。
 * 现在所有安全参数都从这里取，配置才真正生效。
 *
 * @param  string $key     配置键（不含 security. 前缀）
 * @param  mixed  $default 缺省值
 * @return mixed
 */
function securityConfig(string $key, $default = null) {
    global $config;
    return $config['security'][$key] ?? $default;
}

/**
 * 登录凭据有效期（分钟）
 *
 * 来自后台"登录凭据有效期"设置，缺省 30 分钟。
 * 以前是硬编码 INTERVAL 30 MINUTE，后台那个输入框改了没有任何效果。
 *
 * @return int
 */
function tokenExpiryMinutes(): int {
    $mins = (int)getSetting('login_token_expiry', '30');
    return max(5, min(1440, $mins));
}

/**
 * 认证 Cookie 的统一参数
 *
 * Secure 标志跟随真实协议：站内已经是 HTTPS 就一定加 Secure；
 * config 里显式开启也能覆盖（用于 PHP 侧看不到协议的代理场景）。
 * 这样既不会在纯 HTTP 环境把登录搞坏，也不会在 HTTPS 下漏掉防护。
 *
 * @param  int $expires 过期时间戳
 * @return array
 */
function authCookieOptions(int $expires): array {
    return [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => isHttps() || (bool)securityConfig('cookie_secure', false),
        'httponly' => (bool)securityConfig('cookie_httponly', true),
        'samesite' => (string)securityConfig('cookie_samesite', 'Strict'),
    ];
}

/**
 * 写入登录凭据 Cookie
 *
 * @param  string $token 令牌
 * @return void
 */
function setAuthCookie(string $token): void {
    setcookie('museve_token', $token, authCookieOptions(time() + 86400));
}

/**
 * 清除登录凭据 Cookie
 *
 * @return void
 */
function clearAuthCookie(): void {
    setcookie('museve_token', '', authCookieOptions(time() - 3600));
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
        clearAuthCookie();
        jsonResponse(401, '登录已过期，请重新登录');
    }

    // 滑动过期：按设置里的有效期延长
    $mins = tokenExpiryMinutes();
    $stmt = $db->prepare("UPDATE users SET token_expires = DATE_ADD(NOW(), INTERVAL {$mins} MINUTE) WHERE id = ?");
    $stmt->execute([$user['id']]);

    // 供 writeLog 归属操作人（此前 API 请求里 $_SESSION 是空的，日志全记成 user_id=0）
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['admin_id']   = (int)$user['id'];
    $_SESSION['admin_role'] = $user['role'];

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
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', (string)$token)) {
        jsonResponse(403, 'CSRF Token 无效');
    }
}

/**
 * 检查登录失败锁定
 *
 * 双键策略：
 *   1. 主锁 = 同一 IP + 同一账号。攻击者在自己的 IP 上把 admin 刷到锁定，
 *      不会连累站长从另一个 IP 登录 —— 消除了"5 次错密码锁死站长"的认证 DoS；
 *   2. 次锁 = 同一 IP 的总失败数（阈值放宽到 4 倍），用于给撞库横扫垫成本。
 *
 * @param  string $ip      客户端 IP
 * @param  string $account 登录账号
 * @return bool 是否锁定
 */
function checkLoginLock(string $ip, string $account = ''): bool {
    $db = getDB();

    $maxAttempts = max(3, (int)securityConfig('login_max_attempts', 5));
    $windowMin   = max(1, (int)securityConfig('login_lockout_minutes', 30));

    // 主锁：IP + 账号
    if ($account !== '') {
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM login_attempts
             WHERE ip = ? AND account = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL {$windowMin} MINUTE)"
        );
        $stmt->execute([$ip, $account]);
        if ((int)$stmt->fetchColumn() >= $maxAttempts) {
            return true;
        }
    }

    // 次锁：同 IP 跨账号的总失败数
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE ip = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL {$windowMin} MINUTE)"
    );
    $stmt->execute([$ip]);
    return (int)$stmt->fetchColumn() >= $maxAttempts * 4;
}

/**
 * 记录登录失败
 *
 * 顺带修剪过期记录：login_attempts 原先只增不减，长期运行会无限膨胀。
 *
 * @param  string $ip      客户端 IP
 * @param  string $account 登录账号
 * @return void
 */
function recordLoginAttempt(string $ip, string $account): void {
    $db = getDB();

    $stmt = $db->prepare("INSERT INTO `login_attempts` (`ip`, `account`) VALUES (?, ?)");
    $stmt->execute([$ip, $account]);

    // 只保留最近 24 小时的记录，够任何窗口期判断了
    $db->exec("DELETE FROM `login_attempts` WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");
}

/**
 * 清除登录失败记录（登录成功后调用）
 *
 * @param  string $ip      客户端 IP
 * @param  string $account 登录账号
 * @return void
 */
function clearLoginAttempts(string $ip, string $account = ''): void {
    $db = getDB();

    if ($account !== '') {
        $stmt = $db->prepare("DELETE FROM `login_attempts` WHERE ip = ? OR account = ?");
        $stmt->execute([$ip, $account]);
    } else {
        $stmt = $db->prepare("DELETE FROM `login_attempts` WHERE ip = ?");
        $stmt->execute([$ip]);
    }
}
