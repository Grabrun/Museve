<?php
// 后台公共头部
$adminTitle = $siteTitle ?? '暮想管理后台';
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$menuItems = [
    '/admin' => ['icon' => 'ph-chart-pie-slice', 'label' => '仪表盘'],
    '/admin/memories' => ['icon' => 'ph-clock-counter-clockwise', 'label' => '回忆管理'],
    '/admin/whispers' => ['icon' => 'ph-chat-circle-dots', 'label' => '悄悄话'],
    '/admin/articles' => ['icon' => 'ph-article', 'label' => '文章管理'],
    '/admin/users' => ['icon' => 'ph-users', 'label' => '用户管理'],
    '/admin/settings' => ['icon' => 'ph-gear-six', 'label' => '网站设置'],
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminTitle) ?></title>
    <meta name="csrf-token" content="<?= generateCsrfToken() ?>">
    <link rel="icon" href="/resources/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+SC:wght@400;500;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'museve-bg': '#F9F7F4',
                    'museve-haze': '#F5F2F0',
                    'museve-rose': '#DDB8B8',
                    'museve-rose-deep': '#B28B8B',
                    'museve-blue': '#A8C5DA',
                    'museve-night': '#3E3640',
                    'museve-gray': '#8E827F',
                    'museve-green': '#87A878',
                    'museve-orange': '#E0A96D',
                    'museve-red': '#D18B8B',
                    'museve-ash': '#9BADB7',
                },
                fontFamily: {
                    'serif': ['Noto Serif SC', 'serif'],
                    'sans': ['Inter', 'system-ui', 'sans-serif'],
                }
            }
        }
    }
    </script>
    <link rel="stylesheet" href="/resources/css/admin.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#F4F2EF] text-museve-night min-h-screen flex font-sans" x-data="{ sidebarCollapsed: false, sidebarOpen: false }">

<!-- 侧边栏 -->
<aside class="admin-sidebar fixed inset-y-0 left-0 z-30 bg-museve-night text-white transform transition-all duration-300 lg:translate-x-0 flex flex-col"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       :style="sidebarCollapsed ? 'width: 72px' : 'width: 260px'">
    <!-- Logo -->
    <div class="p-5 border-b border-white/10 flex items-center gap-3 overflow-hidden">
        <img src="/resources/images/logo.svg" alt="Logo" class="h-8 w-8 flex-shrink-0">
        <div x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">
            <h1 class="text-lg font-serif font-semibold tracking-wider">暮想</h1>
            <p class="text-[10px] text-white/40">后花园 · 管理后台</p>
        </div>
    </div>

    <!-- 导航菜单 -->
    <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
        <?php foreach ($menuItems as $path => $item): ?>
        <?php $isActive = ($currentPath === $path) || ($path !== '/admin' && strpos($currentPath, $path) === 0); ?>
        <a href="<?= $path ?>"
           class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-all duration-200 group relative
                  <?= $isActive ? 'bg-white/15 text-white' : 'text-white/60 hover:bg-white/8 hover:text-white' ?>"
           :class="sidebarCollapsed ? 'justify-center' : ''">
            <i class="<?= $item['icon'] ?> text-lg flex-shrink-0"></i>
            <span x-show="!sidebarCollapsed" x-transition class="text-sm whitespace-nowrap"><?= $item['label'] ?></span>
            <?php if ($isActive): ?>
            <div class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-5 bg-museve-rose rounded-r-full"></div>
            <?php endif; ?>
            <!-- 折叠态 tooltip -->
            <div x-show="sidebarCollapsed"
                 class="absolute left-full ml-2 px-2 py-1 bg-museve-night text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-50">
                <?= $item['label'] ?>
            </div>
        </a>
        <?php endforeach; ?>
    </nav>

    <!-- 底部操作 -->
    <div class="p-3 border-t border-white/10 space-y-1">
        <button @click="sidebarCollapsed = !sidebarCollapsed"
                class="hidden lg:flex items-center gap-3 w-full px-3 py-2 rounded-lg text-white/50 hover:bg-white/8 hover:text-white transition-colors text-sm"
                :class="sidebarCollapsed ? 'justify-center' : ''">
            <i class="ph ph-sidebar text-lg flex-shrink-0" :class="sidebarCollapsed ? 'ph-arrows-out-simple' : 'ph-arrows-in-simple'"></i>
            <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">收起侧栏</span>
        </button>
        <a href="/" target="_blank"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-white/50 hover:bg-white/8 hover:text-white transition-colors text-sm"
           :class="sidebarCollapsed ? 'justify-center' : ''">
            <i class="ph ph-arrow-square-out text-lg flex-shrink-0"></i>
            <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">查看前台</span>
        </a>
        <button onclick="logout()"
                class="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-white/50 hover:bg-white/8 hover:text-white transition-colors text-sm"
                :class="sidebarCollapsed ? 'justify-center' : ''">
            <i class="ph ph-sign-out text-lg flex-shrink-0"></i>
            <span x-show="!sidebarCollapsed" x-transition class="whitespace-nowrap">退出登录</span>
        </button>
    </div>
</aside>

<!-- 移动端遮罩 -->
<div class="fixed inset-0 bg-black/50 z-20 lg:hidden" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak></div>

<!-- 主内容区 -->
<div class="flex-1 transition-all duration-300" :class="sidebarCollapsed ? 'lg:ml-[72px]' : 'lg:ml-[260px]'">
    <!-- 顶部栏 -->
    <header class="sticky top-0 z-10 bg-white/70 backdrop-blur-xl border-b border-[#E5E0DB]/50 h-[56px] flex items-center justify-between px-6">
        <div class="flex items-center gap-3">
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-museve-night p-1">
                <i class="ph ph-list text-xl"></i>
            </button>
            <div class="text-sm text-museve-gray">欢迎回来，<?= htmlspecialchars($_SESSION['username'] ?? '管理员') ?></div>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-museve-gray/60"><?= date('Y-m-d H:i') ?></span>
        </div>
    </header>

    <!-- 内容区 -->
    <main class="p-6 md:p-8">
