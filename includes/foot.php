<?php
// 暮想 Museve HTML 尾部
$siteTitle = $settings['site_title'] ?? '暮想';
$siteSubtitle = $settings['site_subtitle'] ?? '在薄暮时分，温柔地想起。';
$icp = $settings['icp_number'] ?? '';
$policeIcp = $settings['police_icp'] ?? '';
$copyright = $settings['copyright'] ?? '';
$customFooter = $settings['custom_footer'] ?? '';
?>
</main>

<footer id="museve-footer" class="py-10 text-center text-sm text-[#8E827F] border-t border-[#E5E0DB]/30">
    <div class="max-w-5xl mx-auto px-4">
        <p class="font-serif font-medium text-[#3E3640]"><?= htmlspecialchars($siteTitle) ?></p>
        <p class="mt-1 text-xs"><?= htmlspecialchars($siteSubtitle) ?></p>

        <?php if ($copyright): ?>
        <p class="mt-4 text-xs text-[#8E827F]/60"><?= htmlspecialchars($copyright) ?></p>
        <?php endif; ?>

        <?php if ($icp || $policeIcp): ?>
        <div class="mt-2 text-xs text-[#8E827F]/50 space-x-2">
            <?php if ($icp): ?><a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener" class="hover:text-[#8E827F] transition-colors"><?= htmlspecialchars($icp) ?></a><?php endif; ?>
            <?php if ($policeIcp): ?><a href="http://www.beian.gov.cn/" target="_blank" rel="noopener" class="hover:text-[#8E827F] transition-colors"><?= htmlspecialchars($policeIcp) ?></a><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($customFooter): ?>
        <div class="mt-3 text-xs text-[#8E827F]/60"><?= htmlspecialchars($customFooter) ?></div>
        <?php endif; ?>
    </div>
</footer>

<!-- 移动端底部导航占位 (防止内容被遮挡) -->
<div class="md:hidden h-16"></div>

<script src="https://cdn.jsdelivr.net/npm/pjax@0.2.8/pjax.min.js"></script>
<script src="/resources/js/main.js"></script>
</body>
</html>
