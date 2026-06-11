<?php
// 暮想 Museve 后台入口
session_start();
require __DIR__ . '/../includes/connect.php';

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/admin';

$isPjax = !empty($_SERVER['HTTP_X_PJAX']);

// 检查登录状态
function isAdminLoggedIn(): bool {
    if (!empty($_SESSION['admin_id'])) {
        // 即使 session 存在，也要验证 cookie token 是否过期
        if (!empty($_COOKIE['museve_token'])) {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, account, username, role FROM users WHERE cookie_token = ? AND token_expires > NOW()");
            $stmt->execute([$_COOKIE['museve_token']]);
            $user = $stmt->fetch();
            if ($user) {
                $_SESSION['username'] = $user['username'];
                return true;
            }
        }
        // token 已过期，清除 session 强制重新登录
        session_destroy();
        return false;
    }
    if (!empty($_COOKIE['museve_token'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, account, username, role FROM users WHERE cookie_token = ? AND token_expires > NOW()");
        $stmt->execute([$_COOKIE['museve_token']]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['admin_account'] = $user['account'];
            $_SESSION['admin_role'] = $user['role'];
            return true;
        }
    }
    return false;
}

// /admin/login 路由 — 不需要登录
if ($path === '/admin/login') {
    // 已登录则跳转
    if (isAdminLoggedIn()) {
        header('Location: /admin');
        exit;
    }
    $loginPage = __DIR__ . '/login.php';
    header('Content-Type: text/html; charset=utf-8');
    require $loginPage;
    exit;
}

// 未登录则跳转登录页
if (!isAdminLoggedIn()) {
    header('Location: /admin/login');
    exit;
}

// /admin/api/* 路由
if (preg_match('#^/admin/api/(.+)$#', $path, $m)) {
    // 处理子路径如 auth/logout → auth.php
    $apiPath = $m[1];
    if (strpos($apiPath, '/') !== false) {
        $apiPath = dirname($apiPath) . '.php';
    } else {
        $apiPath = $apiPath . '.php';
    }
    
    // 方法覆盖: POST 请求检查 _method → 绕过 WAF 405 限制
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
    
    $apiFile = __DIR__ . '/api/' . $apiPath;
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'API 不存在'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 后台页面路由
$adminRoutes = [
    '/admin'            => 'sections/home.php',
    '/admin/memories'   => 'sections/memories.php',
    '/admin/whispers'   => 'sections/whispers.php',
    '/admin/articles'   => 'sections/articles.php',
    '/admin/settings'   => 'sections/settings.php',
    '/admin/users'      => 'sections/users.php',
    '/admin/logs'       => 'sections/logs.php',
];

$content = null;
$matched = false;

// 精确路由
if (isset($adminRoutes[$path])) {
    $content = __DIR__ . '/' . $adminRoutes[$path];
    $matched = true;
}

// /admin/articles/edit 和 /admin/articles/edit/{id}
if (!$matched && preg_match('#^/admin/articles/edit(?:/(\d+))?$#', $path, $m)) {
    if (!empty($m[1])) $_GET['id'] = $m[1];
    $content = __DIR__ . '/sections/article-edit.php';
    $matched = true;
}

// 渲染页面
if ($matched && file_exists($content)) {
    // 确保 CSRF Token 已生成（head.php 需要）
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    header('Content-Type: text/html; charset=utf-8');
    
    // Pjax 请求：仅返回内容片段
    if ($isPjax) {
        echo '<meta name="csrf-token" content="' . $_SESSION['csrf_token'] . '">';
        require $content;
        exit;
    }
    
    // 完整页面：使用后台布局（侧边栏 + 顶部栏 + 内容区）
    require __DIR__ . '/includes/head.php';
    require $content;
    require __DIR__ . '/includes/foot.php';
    exit;
}

// 404
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
if ($isPjax) {
    echo '<div class="text-center py-20"><h1 class="text-2xl font-bold">404</h1><p>后台页面未找到</p></div>';
    exit;
}
echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><title>404 - 后台</title></head><body><div style="text-align:center;padding:80px;"><h1>404</h1><p>后台页面未找到</p><a href="/admin">返回后台</a></div></body></html>';
