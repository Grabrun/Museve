<?php
// 404 页面 Section
http_response_code(404);
?>

<section class="min-h-[60vh] flex flex-col items-center justify-center px-4 py-20">
    <div class="text-center">
        <div class="text-7xl mb-6">🌙</div>
        <h1 class="text-4xl md:text-5xl font-serif text-[#3E3640] mb-4">页面走丢了</h1>
        <p class="text-[#8E827F] text-lg mb-8">你要找的页面不在这里，也许它去了暮色的另一边。</p>
        <a href="/" class="inline-block px-8 py-3 rounded-full bg-[#DDB8B8] text-white hover:bg-[#B28B8B] transition-colors shadow-md hover:shadow-lg text-sm">
            返回首页
        </a>
    </div>
</section>
