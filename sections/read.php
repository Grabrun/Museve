<?php
// 文章详情 Section
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
$id = getRouteId();

if (!$id) {
    http_response_code(404);
    if (file_exists(__DIR__ . '/404.php')) require __DIR__ . '/404.php';
    exit;
}

$stmt = $pdo->prepare("SELECT a.*, u.nickname AS author_name FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = :id AND a.status = 'published'");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    if (file_exists(__DIR__ . '/404.php')) require __DIR__ . '/404.php';
    exit;
}

// 上一篇 / 下一篇
$prevStmt = $pdo->prepare("SELECT id, title FROM articles WHERE status = 'published' AND created_at < :created ORDER BY created_at DESC LIMIT 1");
$prevStmt->execute([':created' => $article['created_at']]);
$prevArticle = $prevStmt->fetch();

$nextStmt = $pdo->prepare("SELECT id, title FROM articles WHERE status = 'published' AND created_at > :created ORDER BY created_at ASC LIMIT 1");
$nextStmt->execute([':created' => $article['created_at']]);
$nextArticle = $nextStmt->fetch();

// 阅读时间
$textLength = mb_strlen(strip_tags($article['content'] ?? ''));
$readTime = max(1, (int)ceil($textLength / 500));
?>

<article class="max-w-[720px] mx-auto px-4 py-16">
    <!-- 头部 -->
    <header class="mb-10">
        <?php if (!empty($article['cover'])): ?>
            <img src="<?= htmlspecialchars($article['cover']) ?>"
                 alt="<?= htmlspecialchars($article['title']) ?>"
                 class="w-full h-64 md:h-80 object-cover rounded-2xl mb-8 shadow-lg">
        <?php endif; ?>
        <h1 class="text-3xl md:text-4xl font-serif text-[#3E3640] mb-4 leading-tight">
            <?= htmlspecialchars($article['title']) ?>
        </h1>
        <div class="flex items-center gap-4 text-sm text-[#8E827F]">
            <span><?= htmlspecialchars($article['author_name'] ?? '暮想') ?></span>
            <span>·</span>
            <time><?= htmlspecialchars(date('Y年n月j日', strtotime($article['created_at']))) ?></time>
            <span>·</span>
            <span><?= $readTime ?> 分钟阅读</span>
        </div>
    </header>

    <!-- 正文 -->
    <div class="prose prose-lg max-w-none font-serif text-[#3E3640] leading-relaxed
                prose-headings:text-[#3E3640] prose-a:text-[#DDB8B8] prose-a:no-underline hover:prose-a:underline
                prose-img:rounded-xl prose-blockquote:border-[#DDB8B8] prose-blockquote:text-[#8E827F]">
        <?= $article['content'] ?>
    </div>

    <!-- 上一篇 / 下一篇 -->
    <nav class="mt-16 pt-8 border-t border-[#E5E0DB] flex justify-between gap-4">
        <?php if ($prevArticle): ?>
            <a href="/read?id=<?= (int)$prevArticle['id'] ?>" class="group flex-1 text-left">
                <span class="text-xs text-[#8E827F]">← 上一篇</span>
                <p class="text-[#3E3640] font-serif group-hover:text-[#DDB8B8] transition-colors mt-1 line-clamp-1">
                    <?= htmlspecialchars($prevArticle['title']) ?>
                </p>
            </a>
        <?php else: ?>
            <div class="flex-1"></div>
        <?php endif; ?>

        <?php if ($nextArticle): ?>
            <a href="/read?id=<?= (int)$nextArticle['id'] ?>" class="group flex-1 text-right">
                <span class="text-xs text-[#8E827F]">下一篇 →</span>
                <p class="text-[#3E3640] font-serif group-hover:text-[#DDB8B8] transition-colors mt-1 line-clamp-1">
                    <?= htmlspecialchars($nextArticle['title']) ?>
                </p>
            </a>
        <?php else: ?>
            <div class="flex-1"></div>
        <?php endif; ?>
    </nav>
</article>
