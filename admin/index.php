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
        return true;
    }
    if (!empty($_COOKIE['admin_token'])) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE cookie_token = ? AND token_expires > NOW() AND role = 'admin'");
        $stmt->execute([$_COOKIE['admin_token']]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['admin_id'] = $user['id'];
            return true;
        }
    }
    return false;
}

// /admin/login 路由 — 不需要登录
if ($path === '/admin/login') {
    $isApi = false;
    // 如果是 POST 提交到 login 页面自身
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiFile = __DIR__ . '/api/auth.php';
        if (file_exists($apiFile)) {
            require $apiFile;
            exit;
        }
    }
    // 已登录则跳转
    if (isAdminLoggedIn()) {
        header('Location: /admin');
        exit;
    }
    $loginPage = __DIR__ . '/login.php';
    if ($isPjax) {
        header('Content-Type: text/html; charset=utf-8');
        require $loginPage;
        exit;
    }
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
    $apiFile = __DIR__ . '/api/' . basename($m[1]) . '.php';
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
    '/admin'            => 'sections/dashboard.php',
    '/admin/memories'   => 'sections/memories.php',
    '/admin/whispers'   => 'sections/whispers.php',
    '/admin/articles'   => 'sections/articles.php',
    '/admin/settings'   => 'sections/settings.php',
    '/admin/users'      => 'sections/users.php',
];

$content = null;
$matched = false;

if (isset($adminRoutes[$path])) {
    $content = __DIR__ . '/' . $adminRoutes[$path];
    $matched = true;
}

// Pjax 请求
if ($isPjax && $matched && file_exists($content)) {
    header('Content-Type: text/html; charset=utf-8');
    require $content;
    exit;
}

// 完整页面
if ($matched && file_exists($content)) {
    header('Content-Type: text/html; charset=utf-8');
    // 后台使用自己的布局（可扩展 admin/head.php + admin/foot.php）
    require $content;
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
