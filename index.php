<?php
// 暮想 Museve 前端入口
session_start();
require __DIR__ . '/includes/connect.php';

$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);
$path = rtrim($path, '/') ?: '/';

$isPjax = !empty($_SERVER['HTTP_X_PJAX']);

// 路由表
$routes = [
    '/'           => 'sections/home.php',
    '/memories'   => 'sections/memories.php',
    '/whispers'   => 'sections/whispers.php',
    '/articles'   => 'sections/articles.php',
    '/about'      => 'sections/about.php',
];

$content = null;
$matched = false;

// 精确路由
if (isset($routes[$path])) {
    $content = __DIR__ . '/' . $routes[$path];
    $matched = true;
}

// /memory/{id}
if (!$matched && preg_match('#^/memory/(\d+)$#', $path, $m)) {
    $_GET['id'] = $m[1];
    $content = __DIR__ . '/sections/memory.php';
    $matched = true;
}

// /read/{id}
if (!$matched && preg_match('#^/read/(\d+)$#', $path, $m)) {
    $_GET['id'] = $m[1];
    $content = __DIR__ . '/sections/read.php';
    $matched = true;
}

// /sitemap.xml
if ($path === '/sitemap.xml') {
    require __DIR__ . '/sitemap.php';
    exit;
}

// /robots.txt
if ($path === '/robots.txt') {
    header('Content-Type: text/plain');
    readfile(__DIR__ . '/robots.txt');
    exit;
}

// /api/*
if (!$matched && preg_match('#^/api/(.+)$#', $path, $m)) {
    $apiFile = __DIR__ . '/api/' . basename($m[1]) . '.php';
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
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
    require __DIR__ . '/includes/head.php';
    require $content;
    require __DIR__ . '/includes/foot.php';
    exit;
}

// 404
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
if ($isPjax) {
    echo '<div class="text-center py-20"><h1 class="text-4xl font-bold mb-4">404</h1><p class="text-[#B8A9B0]">页面走丢了…</p></div>';
    exit;
}
require __DIR__ . '/includes/head.php';
echo '<div class="text-center py-20"><h1 class="text-4xl font-bold mb-4">404</h1><p class="text-[#B8A9B0]">页面走丢了…</p></div>';
require __DIR__ . '/includes/foot.php';
