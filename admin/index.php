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

// 渲染页面（注入 CSRF Token meta + 公共脚本）
if ($matched && file_exists($content)) {
    $csrfToken = '';
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];

    header('Content-Type: text/html; charset=utf-8');
    
    // Pjax 请求：仅返回内容片段
    if ($isPjax) {
        // 注入 csrf meta 到内容前
        echo '<meta name="csrf-token" content="' . $csrfToken . '">';
        require $content;
        exit;
    }
    
    // 完整页面：注入最小化 head
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="' . $csrfToken . '"><link rel="icon" href="/resources/images/favicon.png"><link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"><script src="https://unpkg.com/@phosphor-icons/web"><' . '/script><script src="https://cdn.tailwindcss.com">' . '<' . '/script><script>tailwind.config={theme:{extend:{colors:{"museve-bg":"#F9F7F4","museve-haze":"#F5F2F0","museve-rose":"#DDB8B8","museve-rose-deep":"#B28B8B","museve-blue":"#A8C5DA","museve-night":"#3E3640","museve-gray":"#8E827F","museve-green":"#87A878","museve-orange":"#E0A96D","museve-red":"#D18B8B","museve-ash":"#9BADB7"},fontFamily:{serif:["Noto Serif SC","serif"],sans:["Inter","system-ui","sans-serif"]}}}}<' . '/script><link rel="stylesheet" href="/resources/css/admin.css"><script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"><' . '/script><script src="/resources/js/admin.js"><' . '/script></head><body>';
    require $content;
    echo '</body></html>';
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
