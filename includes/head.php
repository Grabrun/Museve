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
$config = require __DIR__ . '/config.php';
$favicon = $config['site']['favicon'];
$logo = $config['site']['logo'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle) ?></title>
    <link rel="icon" href="<?= htmlspecialchars($favicon) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="resources/css/style.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#F9F7F4] text-[#3E3640] min-h-screen" x-data="{ mobileMenu: false }">

<header id="museve-header" class="py-6">
    <div class="max-w-4xl mx-auto px-4 flex items-center justify-between">
        <!-- Logo & Brand -->
        <a href="/" class="flex items-center gap-2" data-pjax>
            <img src="<?= htmlspecialchars($logo) ?>" alt="Logo" class="h-8 w-8">
            <span class="text-xl font-semibold tracking-wide"><?= htmlspecialchars($siteTitle) ?></span>
        </a>

        <!-- Desktop Nav -->
        <nav class="hidden md:flex items-center gap-6 text-sm">
            <a href="/" data-pjax class="hover:text-[#C4A6B8] transition-colors">主页</a>
            <a href="/memories" data-pjax class="hover:text-[#C4A6B8] transition-colors">回忆</a>
            <a href="/whispers" data-pjax class="hover:text-[#C4A6B8] transition-colors">悄悄话</a>
            <a href="/articles" data-pjax class="hover:text-[#C4A6B8] transition-colors">文章</a>
        </nav>

        <!-- Mobile Hamburger -->
        <button class="md:hidden p-2" @click="mobileMenu = !mobileMenu" aria-label="菜单">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <template x-if="!mobileMenu">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </template>
                <template x-if="mobileMenu">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </template>
            </svg>
        </button>
    </div>

    <!-- Mobile Menu -->
    <div x-show="mobileMenu" x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden max-w-4xl mx-auto px-4 pt-4 pb-2">
        <nav class="flex flex-col gap-3 text-sm">
            <a href="/" data-pjax class="hover:text-[#C4A6B8] transition-colors py-1" @click="mobileMenu = false">主页</a>
            <a href="/memories" data-pjax class="hover:text-[#C4A6B8] transition-colors py-1" @click="mobileMenu = false">回忆</a>
            <a href="/whispers" data-pjax class="hover:text-[#C4A6B8] transition-colors py-1" @click="mobileMenu = false">悄悄话</a>
            <a href="/articles" data-pjax class="hover:text-[#C4A6B8] transition-colors py-1" @click="mobileMenu = false">文章</a>
        </nav>
    </div>
</header>

<main id="pjax-container" class="max-w-4xl mx-auto px-4 pb-16 fade-in">
