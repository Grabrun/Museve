<?php
// 文章列表 Section
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
[$page, $per, $offset] = getPagination();

$countStmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT a.*, u.nickname AS author_name FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.status = 'published' ORDER BY a.created_at DESC LIMIT :per OFFSET :offset");
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

/**
 * 估算阅读时间
 */
function estimateReadTime(string $html): int {
    $text = strip_tags($html);
    $chars = mb_strlen($text);
    return max(1, (int)ceil($chars / 500));
}
?>

<section class="max-w-5xl mx-auto px-4 py-16">
    <h2 class="text-3xl font-serif text-[#3E3640] text-center mb-12">文章</h2>

    <?php if (empty($articles)): ?>
        <div class="text-center py-20">
            <div class="text-5xl mb-4">📝</div>
            <p class="text-[#8E827F] text-lg">还没有发布的文章。</p>
        </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($articles as $article): ?>
            <?php
                $readTime = estimateReadTime($article['content'] ?? '');
                $summary = mb_strimwidth(strip_tags($article['content'] ?? ''), 0, 80, '...');
            ?>
            <a href="/read?id=<?= (int)$article['id'] ?>" class="group block bg-white/50 backdrop-blur-md rounded-xl border border-white/50 shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden">
                <!-- 封面图 -->
                <div class="h-44 overflow-hidden">
                    <?php if (!empty($article['cover'])): ?>
                        <img src="<?= htmlspecialchars($article['cover']) ?>"
                             alt="<?= htmlspecialchars($article['title']) ?>"
                             loading="lazy"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-[#DDB8B8]/40 via-[#A8C5DA]/30 to-[#87A878]/20"></div>
                    <?php endif; ?>
                </div>
                <!-- 内容 -->
                <div class="p-5">
                    <h3 class="text-lg font-serif text-[#3E3640] mb-2 line-clamp-2 group-hover:text-[#DDB8B8] transition-colors">
                        <?= htmlspecialchars($article['title']) ?>
                    </h3>
                    <p class="text-sm text-[#8E827F] leading-relaxed mb-4 relative overflow-hidden" style="max-height:3.2em;-webkit-mask-image:linear-gradient(to bottom,black 60%,transparent);mask-image:linear-gradient(to bottom,black 60%,transparent);">
                        <?= htmlspecialchars($summary) ?>
                    </p>
                    <div class="flex items-center justify-between text-xs text-[#8E827F]">
                        <span><?= htmlspecialchars($article['author_name'] ?? '暮想') ?></span>
                        <span class="flex items-center gap-3">
                            <span><?= $readTime ?> 分钟阅读</span>
                            <span><?= htmlspecialchars(date('Y-m-d', strtotime($article['created_at']))) ?></span>
                        </span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($total > $per): ?>
    <nav class="flex justify-center gap-2 mt-12">
        <?php $totalPages = ceil($total / $per); ?>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>"
               class="px-4 py-2 rounded-lg text-sm <?= $p === $page ? 'bg-[#DDB8B8] text-white' : 'bg-white/60 text-[#8E827F] hover:bg-[#DDB8B8]/20' ?> transition-colors">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</section>
