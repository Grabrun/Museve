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
$notFound = false;

// 精确路由
if (isset($routes[$path]) && file_exists(__DIR__ . '/' . $routes[$path])) {
    $content = __DIR__ . '/' . $routes[$path];
    $matched = true;
}

// /memory/{id}
if (!$matched && preg_match('#^/memory/(\d+)$#', $path, $m)) {
    $memId = (int)$m[1];
    // 预检查记忆是否存在
    $stmt = getDB()->prepare("SELECT COUNT(*) FROM memories WHERE id = ?");
    $stmt->execute([$memId]);
    if ($stmt->fetchColumn() > 0) {
        $_GET['id'] = $memId;
        $content = __DIR__ . '/sections/memory.php';
        $matched = true;
    } else {
        $notFound = true;
    }
}

// /read/{id}
if (!$matched && preg_match('#^/read/(\d+)$#', $path, $m)) {
    $readId = (int)$m[1];
    // 预检查文章是否存在且已发布
    $stmt = getDB()->prepare("SELECT COUNT(*) FROM articles WHERE id = ? AND status = 'published'");
    $stmt->execute([$readId]);
    if ($stmt->fetchColumn() > 0) {
        $_GET['id'] = $readId;
        $content = __DIR__ . '/sections/read.php';
        $matched = true;
    } else {
        $notFound = true;
    }
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
if (!$matched && !$notFound && preg_match('#^/api/(.+)$#', $path, $m)) {
    $apiFile = __DIR__ . '/api/' . basename($m[1]) . '.php';
    if (file_exists($apiFile)) {
        require $apiFile;
        exit;
    }
    $notFound = true;
}

// 404 处理（头部设置必须在任何输出之前）
if ($notFound || !$matched || !$content || !file_exists($content)) {
    $content = __DIR__ . '/sections/404.php';
    $is404 = true;
} else {
    $is404 = false;
}

// Pjax 请求
if ($isPjax && !$is404 && file_exists($content)) {
    header('Content-Type: text/html; charset=utf-8');
    require $content;
    exit;
}
if ($isPjax && $is404) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    require $content;
    exit;
}

// 完整页面
header('Content-Type: text/html; charset=utf-8');
if ($is404) {
    http_response_code(404);
}
require __DIR__ . '/includes/head.php';
require $content;
require __DIR__ . '/includes/foot.php';
