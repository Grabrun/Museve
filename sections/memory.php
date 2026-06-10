<?php
/**
 * 回忆详情页
 * 展示单篇回忆的完整内容
 */
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
$id = getRouteId();

if (!$id) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username AS author_name, u.avatar AS author_avatar
        FROM memories m
        LEFT JOIN users u ON m.author_id = u.id
        WHERE m.id = :id
    ");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $memory = $stmt->fetch();

    if (!$memory) {
        http_response_code(404);
        require __DIR__ . '/404.php';
        exit;
    }

    // 上一篇 / 下一篇
    $prevStmt = $pdo->prepare("SELECT id, title FROM memories WHERE event_time < :time ORDER BY event_time DESC LIMIT 1");
    $prevStmt->execute([':time' => $memory['event_time']]);
    $prevMemory = $prevStmt->fetch();

    $nextStmt = $pdo->prepare("SELECT id, title FROM memories WHERE event_time > :time ORDER BY event_time ASC LIMIT 1");
    $nextStmt->execute([':time' => $memory['event_time']]);
    $nextMemory = $nextStmt->fetch();

} catch (PDOException $e) {
    http_response_code(500);
    echo '<div class="text-center py-20"><p class="text-red-500">服务器错误，请稍后重试</p></div>';
    exit;
}
?>

<section class="max-w-[720px] mx-auto px-4 py-16 bg-noise">
    <!-- 返回按钮 -->
    <a href="/memories" data-pjax class="inline-flex items-center gap-1.5 text-sm text-[#8E827F] hover:text-[#DDB8B8] transition-colors mb-8">
        <i class="ph ph-arrow-left"></i>
        <span>返回时光轴</span>
    </a>

    <!-- 标题 -->
    <h1 class="text-[2.25rem] font-serif font-bold text-[#3E3640] leading-tight mb-4">
        <?= htmlspecialchars($memory['title']) ?>
    </h1>

    <!-- 元信息 -->
    <div class="flex flex-wrap items-center gap-4 text-sm text-[#8E827F] mb-8">
        <span class="inline-flex items-center gap-1.5">
            <i class="ph ph-calendar"></i>
            <?= date('Y年m月d日', strtotime($memory['event_time'])) ?>
        </span>
        <?php if (!empty($memory['author_name'])): ?>
        <span class="inline-flex items-center gap-1.5">
            <i class="ph ph-user"></i>
            <?= htmlspecialchars($memory['author_name']) ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- 封面图 -->
    <?php if (!empty($memory['image'])): ?>
    <div class="mb-10 rounded-2xl overflow-hidden">
        <img src="<?= htmlspecialchars($memory['image']) ?>"
             alt="<?= htmlspecialchars($memory['title']) ?>"
             loading="lazy"
             class="w-full max-h-[500px] object-cover">
    </div>
    <?php endif; ?>

    <!-- 正文 -->
    <div class="prose-museve font-serif text-[#3E3640]">
        <?= nl2br(htmlspecialchars($memory['content'] ?? '')) ?>
    </div>

    <!-- 上一篇/下一篇导航 -->
    <div class="mt-16 pt-8 border-t border-[#E5E0DB]/50">
        <div class="flex justify-between gap-4">
            <?php if ($prevMemory): ?>
            <a href="/memory/<?= $prevMemory['id'] ?>" data-pjax class="group flex-1">
                <div class="text-xs text-[#8E827F] mb-1">
                    <i class="ph ph-arrow-left mr-1"></i> 上一篇
                </div>
                <div class="text-sm font-medium text-[#3E3640] group-hover:text-[#DDB8B8] transition-colors line-clamp-1">
                    <?= htmlspecialchars($prevMemory['title']) ?>
                </div>
            </a>
            <?php else: ?>
            <div class="flex-1"></div>
            <?php endif; ?>

            <?php if ($nextMemory): ?>
            <a href="/memory/<?= $nextMemory['id'] ?>" data-pjax class="group flex-1 text-right">
                <div class="text-xs text-[#8E827F] mb-1">
                    下一篇 <i class="ph ph-arrow-right ml-1"></i>
                </div>
                <div class="text-sm font-medium text-[#3E3640] group-hover:text-[#DDB8B8] transition-colors line-clamp-1">
                    <?= htmlspecialchars($nextMemory['title']) ?>
                </div>
            </a>
            <?php else: ?>
            <div class="flex-1"></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.prose-museve {
    font-size: 1.125rem;
    line-height: 1.8;
}
.prose-museve p {
    margin-bottom: 1.2em;
}
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
