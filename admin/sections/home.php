<?php
// 后台仪表盘
$db = getDB();

// 默认值（防止查询异常时模板变量未定义）
$stats = [];
$activities = [];
$diskUsed = 0;
$diskPercent = 0;
$diskLimit = 500;
$phpVersion = PHP_VERSION;
$mysqlVersion = '未知';
$cards = [];

try {
    $tables = ['memories', 'whispers', 'articles', 'users'];

    // 统计各表总数
    foreach ($tables as $table) {
        $stats[$table] = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
    }

    // 本周趋势
    $weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
    foreach ($tables as $table) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM $table WHERE created_at > ?");
        $stmt->execute([$weekAgo]);
        $stats[$table . '_week'] = $stmt->fetchColumn();
    }

    // 最近活动
    $recentMemories = $db->query("SELECT '回忆' as type, title as content, created_at FROM memories ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    $recentWhispers = $db->query("SELECT '悄悄话' as type, LEFT(content, 50) as content, created_at FROM whispers ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    $recentArticles = $db->query("SELECT '文章' as type, title as content, created_at FROM articles ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

    $activities = array_merge($recentMemories, $recentWhispers, $recentArticles);
    usort($activities, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    $activities = array_slice($activities, 0, 5);

    // MySQL 版本
    $mysqlVersion = $db->query("SELECT VERSION()")->fetchColumn();

    // 统计卡片配置
    $cards = [
        ['label' => '回忆', 'count' => $stats['memories'] ?? 0, 'week' => $stats['memories_week'] ?? 0, 'color' => '#DDB8B8', 'icon' => 'ph-clock-counter-clockwise'],
        ['label' => '悄悄话', 'count' => $stats['whispers'] ?? 0, 'week' => $stats['whispers_week'] ?? 0, 'color' => '#A8C5DA', 'icon' => 'ph-chat-circle-dots'],
        ['label' => '文章', 'count' => $stats['articles'] ?? 0, 'week' => $stats['articles_week'] ?? 0, 'color' => '#87A878', 'icon' => 'ph-article'],
        ['label' => '用户', 'count' => $stats['users'] ?? 0, 'week' => $stats['users_week'] ?? 0, 'color' => '#E0A96D', 'icon' => 'ph-users'],
    ];
} catch (PDOException $e) {
    error_log('[Museve] 仪表盘查询失败: ' . $e->getMessage());
}

// 磁盘使用（文件系统操作，单独处理）
$uploadDir = __DIR__ . '/../../uploads';
$totalSize = 0;
try {
    if (is_dir($uploadDir)) {
        $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadDir));
        foreach ($iter as $file) {
            if ($file->isFile()) $totalSize += $file->getSize();
        }
    }
} catch (Exception $e) {
    error_log('[Museve] 磁盘扫描失败: ' . $e->getMessage());
}
$diskUsed = round($totalSize / 1024 / 1024, 2);
$diskPercent = min(100, round($diskUsed / $diskLimit * 100));
?>

<div class="mb-8">
    <h1 class="text-2xl font-serif text-[#3E3640]">仪表盘</h1>
    <p class="text-sm text-[#8E827F] mt-1">暮想后花园 · 一览全局</p>
</div>

<!-- 统计卡片 -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <?php foreach ($cards as $card): ?>
    <div class="bg-white rounded-xl p-5 border-l-4 hover:shadow-md transition-shadow" style="border-color: <?= $card['color'] ?>">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm text-[#8E827F]"><?= $card['label'] ?></span>
            <i class="<?= $card['icon'] ?> text-lg" style="color: <?= $card['color'] ?>"></i>
        </div>
        <div class="text-3xl font-bold text-[#3E3640]"><?= $card['count'] ?></div>
        <?php if ($card['week'] > 0): ?>
        <div class="flex items-center gap-1 mt-2 text-xs">
            <span class="text-[#87A878]">↑ <?= $card['week'] ?></span>
            <span class="text-[#8E827F]">本周新增</span>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- 磁盘使用 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-base font-semibold text-[#3E3640] mb-4">磁盘使用</h2>
        <div class="flex items-center justify-center mb-4">
            <div class="relative w-28 h-28">
                <svg class="w-28 h-28 transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#F5F2F0" stroke-width="8"/>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#DDB8B8" stroke-width="8"
                            stroke-dasharray="<?= $diskPercent * 2.64 ?> <?= 264 - $diskPercent * 2.64 ?>"
                            stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-lg font-bold text-[#3E3640]"><?= $diskPercent ?>%</span>
                    <span class="text-[10px] text-[#8E827F]"><?= $diskUsed ?>MB</span>
                </div>
            </div>
        </div>
        <p class="text-xs text-center text-[#8E827F]">已用 <?= $diskUsed ?>MB / <?= $diskLimit ?>MB</p>
    </div>

    <!-- 系统信息 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-base font-semibold text-[#3E3640] mb-4">系统信息</h2>
        <div class="space-y-3">
            <div class="flex justify-between text-sm">
                <span class="text-[#8E827F]">PHP 版本</span>
                <span class="text-[#3E3640] font-medium"><?= $phpVersion ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-[#8E827F]">MySQL 版本</span>
                <span class="text-[#3E3640] font-medium"><?= $mysqlVersion ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-[#8E827F]">服务器时间</span>
                <span class="text-[#3E3640] font-medium"><?= date('Y-m-d H:i') ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-[#8E827F]">上传目录</span>
                <span class="text-[#3E3640] font-medium"><?= is_writable($uploadDir) ? '✅ 可写' : '❌ 不可写' ?></span>
            </div>
        </div>
    </div>

    <!-- 最近活动 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-base font-semibold text-[#3E3640] mb-4">最近活动</h2>
        <div class="space-y-3">
            <?php if (empty($activities)): ?>
            <p class="text-sm text-[#8E827F] text-center py-4">暂无活动</p>
            <?php else: foreach ($activities as $act): ?>
            <div class="flex items-start gap-3">
                <div class="w-1.5 h-1.5 rounded-full bg-[#DDB8B8] mt-2 flex-shrink-0"></div>
                <div class="min-w-0">
                    <span class="text-[10px] text-[#A8C5DA] uppercase tracking-wider"><?= htmlspecialchars($act['type']) ?></span>
                    <p class="text-sm text-[#3E3640] truncate"><?= htmlspecialchars($act['content']) ?></p>
                    <span class="text-xs text-[#8E827F]"><?= date('m-d H:i', strtotime($act['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
