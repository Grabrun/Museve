<?php
// 暮想 Museve HTML 头部
$db = getDB();
$settingsStmt = $db->query("SELECT `key`, `value` FROM `settings`");
$settings = [];
while ($row = $settingsStmt->fetch()) {
    $settings[$row['key']] = $row['value'];
}
$siteTitle = $settings['site_title'] ?? '暮想';
$siteSubtitle = $settings['site_subtitle'] ?? '在薄暮时分，温柔地想起。';
$siteDescription = $settings['site_description'] ?? $siteSubtitle;
$siteKeywords = $settings['site_keywords'] ?? '暮想,Museve,回忆,悄悄话,文章,时光,纪念';
$config = require __DIR__ . '/config.php';
$favicon = $config['site']['favicon'];
$logo = $settings['site_logo'] ?: ($config['site']['logo'] ?? '/resources/images/logo.svg');
$logoFavicon = $settings['site_logo'] ?? '/resources/images/logo.svg';
$cacheVer = $settings['cache_version'] ?? '1';

/**
 * 给静态资源URL附加缓存版本号，修改后浏览器自动刷新
 */
function cacheBust(string $url, string $ver): string {
    $sep = strpos($url, '?') === false ? '?' : '&';
    return $url . $sep . 'v=' . urlencode($ver);
}

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
    <link rel="icon" href="<?= htmlspecialchars(cacheBust($favicon, $cacheVer)) ?>">

    <!-- 字体: Noto Serif SC (正文) + Inter (UI) + ZCOOL XiaoWei (品牌手写) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;500;600;700&family=Inter:wght@300;400;500;600&family=ZCOOL+XiaoWei&family=Caveat:wght@400;500&display=swap" rel="stylesheet">

    <!-- Phosphor Icons (线形 1.5px 描边) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'museve-bg': '#F9F7F4',
                        'museve-rose': '#DDB8B8',
                        'museve-rose-deep': '#B28B8B',
                        'museve-haze': '#F5F2F0',
                        'museve-blue': '#A8C5DA',
                        'museve-night': '#3E3640',
                        'museve-gray': '#8E827F',
                        'museve-green': '#87A878',
                        'museve-orange': '#E0A96D',
                        'museve-red': '#D18B8B',
                        'museve-ash': '#9BADB7',
                    },
                    fontFamily: {
                        'serif': ['Noto Serif SC', 'Source Han Serif SC', 'serif'],
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                        'hand': ['ZCOOL XiaoWei', 'Caveat', 'cursive'],
                    }
                }
            }
        }
    </script>

    <link rel="stylesheet" href="/resources/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-museve-bg text-museve-night min-h-screen font-sans" x-data="{ mobileMenu: false }">

<header id="museve-header" class="sticky top-0 z-50 bg-white/70 backdrop-blur-xl border-b border-[#E5E0DB]/50">
    <div class="max-w-5xl mx-auto px-4 py-4 flex items-center justify-between">
        <!-- Logo & Brand -->
        <a href="/" class="flex items-center gap-2.5" data-pjax>
            <img src="<?= htmlspecialchars(cacheBust($logo, $cacheVer)) ?>" alt="Logo" class="h-8 w-8">
            <div>
                <span class="text-lg font-serif font-semibold tracking-wide"><?= htmlspecialchars($siteTitle) ?></span>
                <span class="hidden sm:inline text-xs text-museve-gray ml-2 font-hand"><?= htmlspecialchars($siteSubtitle) ?></span>
            </div>
        </a>

        <!-- Desktop Nav with sliding indicator -->
        <nav class="hidden md:flex items-center gap-1 text-sm relative" id="main-nav">
            <?php foreach ($navItems as $path => $label): ?>
            <a href="<?= $path ?>" data-pjax
               class="nav-link px-4 py-2 rounded-full transition-all duration-300 <?= $currentPath === $path ? 'text-museve-rose bg-museve-rose/10 font-medium' : 'text-museve-gray hover:text-museve-night hover:bg-museve-haze' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- Mobile Hamburger -->
        <button class="md:hidden p-2 rounded-lg hover:bg-museve-haze transition-colors" @click="mobileMenu = !mobileMenu" aria-label="菜单">
            <i class="ph ph-list text-xl" x-show="!mobileMenu"></i>
            <i class="ph ph-x text-xl" x-show="mobileMenu"></i>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenu" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden border-t border-[#E5E0DB]/50 bg-white/90 backdrop-blur-xl">
        <nav class="max-w-5xl mx-auto px-4 py-3 flex flex-col gap-1">
            <?php foreach ($navItems as $path => $label): ?>
            <a href="<?= $path ?>" data-pjax @click="mobileMenu = false"
               class="px-4 py-2.5 rounded-lg text-sm transition-colors <?= $currentPath === $path ? 'text-museve-rose bg-museve-rose/10 font-medium' : 'text-museve-gray hover:bg-museve-haze' ?>">
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
