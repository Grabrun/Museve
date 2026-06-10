<?php
// 后台仪表盘
$db = getDB();

$stats = [];
$tables = ['memories', 'whispers', 'articles', 'users'];
foreach ($tables as $table) {
    $stats[$table] = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
}

// 最近活动
$recentMemories = $db->query("SELECT '回忆' as type, title as content, created_at FROM memories ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$recentWhispers = $db->query("SELECT '悄悄话' as type, LEFT(content, 50) as content, created_at FROM whispers ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
$recentArticles = $db->query("SELECT '文章' as type, title as content, created_at FROM articles ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

$activities = array_merge($recentMemories, $recentWhispers, $recentArticles);
usort($activities, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
$activities = array_slice($activities, 0, 5);
?>

<div class="mb-8">
    <h1 class="text-2xl font-serif text-[#3E3640]">仪表盘</h1>
    <p class="text-sm text-[#8E827F] mt-1">暮想后花园 · 一览全局</p>
</div>

<!-- 统计卡片 -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <div class="bg-white rounded-xl p-5 border-l-4 border-[#DDB8B8]">
        <div class="text-sm text-[#8E827F]">回忆</div>
        <div class="text-3xl font-bold text-[#3E3640] mt-1"><?= $stats['memories'] ?></div>
    </div>
    <div class="bg-white rounded-xl p-5 border-l-4 border-[#A8C5DA]">
        <div class="text-sm text-[#8E827F]">悄悄话</div>
        <div class="text-3xl font-bold text-[#3E3640] mt-1"><?= $stats['whispers'] ?></div>
    </div>
    <div class="bg-white rounded-xl p-5 border-l-4 border-[#87A878]">
        <div class="text-sm text-[#8E827F]">文章</div>
        <div class="text-3xl font-bold text-[#3E3640] mt-1"><?= $stats['articles'] ?></div>
    </div>
    <div class="bg-white rounded-xl p-5 border-l-4 border-[#E0A96D]">
        <div class="text-sm text-[#8E827F]">用户</div>
        <div class="text-3xl font-bold text-[#3E3640] mt-1"><?= $stats['users'] ?></div>
    </div>
</div>

<!-- 最近活动 -->
<div class="bg-white rounded-xl p-6">
    <h2 class="text-lg font-semibold text-[#3E3640] mb-4">最近活动</h2>
    <div class="space-y-4">
        <?php foreach ($activities as $act): ?>
        <div class="flex items-start gap-3">
            <div class="w-2 h-2 rounded-full bg-[#DDB8B8] mt-2 flex-shrink-0"></div>
            <div>
                <span class="text-xs text-[#8E827F]"><?= htmlspecialchars($act['type']) ?></span>
                <p class="text-sm text-[#3E3640]"><?= htmlspecialchars($act['content']) ?></p>
                <span class="text-xs text-[#8E827F]"><?= htmlspecialchars($act['created_at']) ?></span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
