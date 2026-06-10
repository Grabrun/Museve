<?php
// 暮想 首页
$db = getDB();
$memories = $db->query("SELECT * FROM memories ORDER BY created_at DESC LIMIT 6")->fetchAll();
$whispers = $db->query("SELECT * FROM whispers ORDER BY created_at DESC LIMIT 3")->fetchAll();
$articles = $db->query("SELECT * FROM articles WHERE status='published' ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>
<div class="py-12">
    <div class="text-center mb-16">
        <h1 class="text-3xl font-bold mb-3">暮想</h1>
        <p class="text-[#B8A9B0]">在薄暮时分，温柔地想起。</p>
    </div>

    <section class="mb-16">
        <h2 class="text-xl font-semibold mb-6">✨ 最近回忆</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($memories as $m): ?>
            <a href="/read/<?= $m['id'] ?>" data-pjax class="block bg-white rounded-xl p-4 border border-[#EDE8E4] hover:shadow-sm transition-shadow">
                <?php if ($m['image']): ?>
                <img src="<?= htmlspecialchars($m['image']) ?>" alt="" class="w-full h-40 object-cover rounded-lg mb-3">
                <?php endif; ?>
                <h3 class="font-medium"><?= htmlspecialchars($m['title']) ?></h3>
                <p class="text-xs text-[#B8A9B0] mt-1"><?= $m['event_time'] ? date('Y-m-d', strtotime($m['event_time'])) : '' ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-16">
        <h2 class="text-xl font-semibold mb-6">🌙 悄悄话</h2>
        <div class="space-y-3">
            <?php foreach ($whispers as $w): ?>
            <div class="bg-white rounded-xl p-4 border border-[#EDE8E4]">
                <p class="text-sm leading-relaxed"><?= nl2br(htmlspecialchars($w['content'])) ?></p>
                <p class="text-xs text-[#B8A9B0] mt-2"><?= date('Y-m-d H:i', strtotime($w['created_at'])) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section>
        <h2 class="text-xl font-semibold mb-6">📖 文章</h2>
        <div class="space-y-3">
            <?php foreach ($articles as $a): ?>
            <a href="/read/<?= $a['id'] ?>" data-pjax class="block bg-white rounded-xl p-4 border border-[#EDE8E4] hover:shadow-sm transition-shadow">
                <h3 class="font-medium"><?= htmlspecialchars($a['title']) ?></h3>
                <p class="text-xs text-[#B8A9B0] mt-1"><?= date('Y-m-d', strtotime($a['created_at'])) ?></p>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>
