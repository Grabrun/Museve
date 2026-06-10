<?php
// 后台公共头部
$adminTitle = $siteTitle ?? '暮想管理后台';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminTitle) ?></title>
    <link rel="icon" href="<?= $siteFavicon ?? '' ?>">
    <link rel="stylesheet" href="/resources/css/admin.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-[#F4F2EF] text-[#3E3640] min-h-screen flex" x-data="{ sidebarOpen: false }">

<!-- 侧边栏 -->
<aside class="admin-sidebar fixed inset-y-0 left-0 z-30 w-[260px] bg-[#3E3640] text-white transform transition-transform duration-300 lg:translate-x-0"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
    <div class="p-6 border-b border-white/10">
        <h1 class="text-2xl font-serif tracking-wider">暮想</h1>
        <p class="text-xs text-white/50 mt-1">后花园 · 管理后台</p>
    </div>
    <nav class="p-4 space-y-1">
        <a href="/admin" class="admin-menu-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors">
            <span>📊</span><span>仪表盘</span>
        </a>
        <a href="/admin/memories" class="admin-menu-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors">
            <span>🕰️</span><span>回忆管理</span>
        </a>
        <a href="/admin/whispers" class="admin-menu-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors">
            <span>💬</span><span>悄悄话</span>
        </a>
        <a href="/admin/articles" class="admin-menu-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors">
            <span>📝</span><span>文章管理</span>
        </a>
        <a href="/admin/users" class="admin-menu-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors">
            <span>👥</span><span>用户管理</span>
        </a>
        <a href="/admin/settings" class="admin-menu-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition-colors">
            <span>⚙️</span><span>网站设置</span>
        </a>
    </nav>
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-white/10">
        <a href="/" target="_blank" class="flex items-center gap-2 text-sm text-white/60 hover:text-white transition-colors">
            <span>🌐</span><span>查看前台</span>
        </a>
        <button onclick="logout()" class="flex items-center gap-2 text-sm text-white/60 hover:text-white transition-colors mt-2">
            <span>🚪</span><span>退出登录</span>
        </button>
    </div>
</aside>

<!-- 移动端遮罩 -->
<div class="fixed inset-0 bg-black/50 z-20 lg:hidden" x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak></div>

<!-- 主内容区 -->
<div class="flex-1 lg:ml-[260px]">
    <!-- 顶部栏 -->
    <header class="sticky top-0 z-10 bg-white/60 backdrop-blur-xl border-b border-[#E5E0DB] h-[60px] flex items-center justify-between px-6">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-[#3E3640]">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <div class="text-sm text-[#8E827F]">欢迎回来，<?= htmlspecialchars($_SESSION['username'] ?? '管理员') ?></div>
    </header>

    <!-- 内容区 -->
    <main class="p-6 md:p-8">
