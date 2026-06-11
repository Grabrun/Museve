<?php
// 404 页面 Section
?>

<section class="min-h-[70vh] flex flex-col items-center justify-center px-4 py-20">
    <div class="text-center max-w-lg">
        <!-- 大号 404 数字 -->
        <div class="mb-6">
            <span class="text-[10rem] md:text-[12rem] font-bold leading-none select-none bg-gradient-to-br from-[#DDB8B8] via-[#A8C5DA] to-[#DDB8B8] bg-clip-text text-transparent drop-shadow-sm">404</span>
        </div>

        <!-- 月亮装饰 -->
        <div class="mb-6">
            <div class="w-16 h-16 mx-auto rounded-full bg-gradient-to-br from-[#DDB8B8]/30 to-[#A8C5DA]/30 backdrop-blur-md flex items-center justify-center shadow-md">
                <i class="ph ph-moon-stars text-2xl text-[#DDB8B8]"></i>
            </div>
        </div>

        <!-- 标题 -->
        <h1 class="text-3xl md:text-4xl font-serif text-[#3E3640] mb-4">页面走丢了</h1>

        <!-- 描述 -->
        <p class="text-[#8E827F] text-lg mb-3 leading-relaxed">
            你要找的页面不在这里，也许它去了暮色的另一边。
        </p>
        <p class="text-[#A8C5DA] text-sm mb-10">
            不过没关系，回忆总会带你回到对的地方 ✨
        </p>

        <!-- 返回首页按钮 - 毛玻璃样式 -->
        <a href="/"
           class="inline-flex items-center gap-2 px-8 py-3 rounded-full bg-white/40 backdrop-blur-md border border-white/50 text-[#3E3640] hover:bg-[#DDB8B8] hover:text-white hover:border-[#DDB8B8] transition-all shadow-md hover:shadow-lg text-sm font-medium group">
            <i class="ph ph-house text-lg group-hover:-translate-x-0.5 transition-transform"></i>
            返回首页
        </a>

        <!-- 装饰性小元素 -->
        <div class="mt-16 flex items-center justify-center gap-6 text-[#DDB8B8]/50">
            <i class="ph ph-flower-tulip text-xl"></i>
            <i class="ph ph-star text-sm"></i>
            <i class="ph ph-heart text-lg"></i>
            <i class="ph ph-star text-sm"></i>
            <i class="ph ph-flower-tulip text-xl"></i>
        </div>
    </div>
</section>
