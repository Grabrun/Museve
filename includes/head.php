<?php
// 暮想 Museve HTML 头部
// 设置统一走 getSettings()（请求内静态缓存），不再每个模板各查一遍库
$settings = getSettings();

$siteTitle       = getSetting('site_title', '暮想');
$siteSubtitle    = getSetting('site_subtitle', '在薄暮时分，温柔地想起。');
$siteDescription = getSetting('site_description', $siteSubtitle);
$siteKeywords    = getSetting('site_keywords', '暮想,Museve,回忆,悄悄话,文章,时光,纪念');

$config = require __DIR__ . '/config.php';
$favicon = $config['site']['favicon'];
$logo = $settings['site_logo'] ?: ($config['site']['logo'] ?? '/resources/images/logo.svg');

// 导航高亮
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$navItems = [
    '/' => '主页',
    '/memories' => '回忆',
    '/whispers' => '悄悄话',
    '/articles' => '文章',
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($siteDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($siteKeywords) ?>">
    <meta name="author" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($siteDescription) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="<?= htmlspecialchars($siteTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($siteDescription) ?>">
    <meta name="theme-color" content="#DDB8B8">
    <link rel="icon" href="<?= htmlspecialchars(assetUrl($favicon)) ?>">

    <!-- 字体: Noto Serif SC (正文) + Inter (UI) + ZCOOL XiaoWei (品牌手写) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=ZCOOL+XiaoWei&family=Caveat:wght@400;500&display=swap" rel="stylesheet">

    <!-- 图标: Phosphor（本地化，regular 字重） -->
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('/resources/vendor/phosphor/style.css')) ?>">

    <!-- Tailwind: 构建期预编译产物，替代 cdn.tailwindcss.com 的浏览器端 JIT -->
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('/resources/css/tailwind.css')) ?>">

    <!-- 项目自有样式与设计令牌 -->
    <link rel="stylesheet" href="<?= htmlspecialchars(assetUrl('/resources/css/style.css')) ?>">
</head>
<body class="bg-museve-bg text-museve-night min-h-screen font-sans">

<header id="museve-header" class="sticky top-0 z-50 bg-white/70 backdrop-blur-xl border-b border-[#E5E0DB]/50">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <!-- Logo & Brand -->
        <a href="/" class="flex items-center gap-2.5" data-pjax>
            <img src="<?= htmlspecialchars(assetUrl($logo)) ?>" alt="Logo" class="h-8 w-8">
            <div>
                <span class="text-lg font-serif font-semibold tracking-wide"><?= htmlspecialchars($siteTitle) ?></span>
                <span class="hidden sm:inline text-xs text-museve-gray ml-2 font-hand"><?= htmlspecialchars($siteSubtitle) ?></span>
            </div>
        </a>

        <!-- 桌面导航 -->
        <nav class="hidden md:flex items-center gap-1 text-sm relative" id="main-nav">
            <?php foreach ($navItems as $path => $label): ?>
            <a href="<?= $path ?>" data-pjax
               class="nav-link px-4 py-2 rounded-full transition-all duration-300 <?= $currentPath === $path ? 'text-museve-rose bg-museve-rose/10 font-medium' : 'text-museve-gray hover:text-museve-night hover:bg-museve-haze' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</header>

<!-- 移动端底部固定导航 -->
<nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-xl border-t border-[#E5E0DB]/50 safe-area-bottom">
    <div class="flex items-center justify-around py-2">
        <a href="/" data-pjax class="flex flex-col items-center gap-0.5 px-3 py-1 <?= $currentPath === '/' ? 'text-museve-rose' : 'text-museve-gray' ?>">
            <i class="ph ph-house text-lg"></i>
            <span class="text-[10px]">主页</span>
        </a>
        <a href="/memories" data-pjax class="flex flex-col items-center gap-0.5 px-3 py-1 <?= $currentPath === '/memories' ? 'text-museve-rose' : 'text-museve-gray' ?>">
            <i class="ph ph-clock-counter-clockwise text-lg"></i>
            <span class="text-[10px]">回忆</span>
        </a>
        <a href="/whispers" data-pjax class="flex flex-col items-center gap-0.5 px-3 py-1 <?= $currentPath === '/whispers' ? 'text-museve-rose' : 'text-museve-gray' ?>">
            <i class="ph ph-chat-circle-dots text-lg"></i>
            <span class="text-[10px]">悄悄话</span>
        </a>
        <a href="/articles" data-pjax class="flex flex-col items-center gap-0.5 px-3 py-1 <?= $currentPath === '/articles' ? 'text-museve-rose' : 'text-museve-gray' ?>">
            <i class="ph ph-article text-lg"></i>
            <span class="text-[10px]">文章</span>
        </a>
    </div>
</nav>

<main id="pjax-container" class="max-w-5xl mx-auto px-4 pb-24 md:pb-16 fade-in">
