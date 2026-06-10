<?php
// 暮想 Museve HTML 尾部
$config = require __DIR__ . '/config.php';
$siteTitle = $config['site']['title'];
$siteSubtitle = $config['site']['subtitle'];
?>
</main>

<footer id="museve-footer" class="py-8 text-center text-sm text-[#B8A9B0]">
    <div class="max-w-4xl mx-auto px-4">
        <p class="font-medium"><?= htmlspecialchars($siteTitle) ?></p>
        <p class="mt-1"><?= htmlspecialchars($siteSubtitle) ?></p>
        <p class="mt-3 text-xs">&copy; <?= date('Y') ?> <?= htmlspecialchars($siteTitle) ?> · All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/pjax@0.2.8/pjax.min.js"></script>
<script src="resources/js/main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var pjax = new Pjax({
        selectors: ['title', '#pjax-container'],
        cacheBust: false,
        scrollTo: 0
    });

    document.addEventListener('pjax:send', function() {
        var container = document.getElementById('pjax-container');
        if (container) {
            container.classList.remove('fade-in');
            container.classList.add('fade-out');
        }
    });

    document.addEventListener('pjax:complete', function() {
        var container = document.getElementById('pjax-container');
        if (container) {
            container.classList.remove('fade-out');
            container.classList.add('fade-in');
        }
    });
});
</script>
</body>
</html>
