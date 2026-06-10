<?php
// 暮想 Museve HTML 尾部
$config = require __DIR__ . '/config.php';
$siteTitle = $config['site']['title'];
$siteSubtitle = $config['site']['subtitle'];
?>
</main>

<footer id="museve-footer" class="py-10 text-center text-sm text-[#8E827F] border-t border-[#E5E0DB]/30">
    <div class="max-w-5xl mx-auto px-4">
        <p class="font-serif font-medium text-[#3E3640]"><?= htmlspecialchars($siteTitle) ?></p>
        <p class="mt-1 text-xs"><?= htmlspecialchars($siteSubtitle) ?></p>
        <p class="mt-4 text-xs text-[#8E827F]/60">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?> · 在薄暮时分，温柔地想起。</p>
    </div>
</footer>

<!-- 移动端底部导航占位 (防止内容被遮挡) -->
<div class="md:hidden h-16"></div>

<script src="https://cdn.jsdelivr.net/npm/pjax@0.2.8/pjax.min.js"></script>
<script src="/resources/js/main.js"></script>
</body>
</html>
