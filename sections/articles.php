<?php
// 文章列表 Section
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
[$page, $per, $offset] = getPagination();
$search = trim($_GET['search'] ?? '');

$where = "WHERE a.status = 'published'";
$params = [];
if ($search) {
    $where .= " AND a.title LIKE :search";
    $params[':search'] = "%$search%";
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles a $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT a.*, u.username AS author_name, u.avatar AS author_avatar FROM articles a LEFT JOIN users u ON a.author_id = u.id $where ORDER BY a.created_at DESC LIMIT :per OFFSET :offset");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$articles = $stmt->fetchAll();

/**
 * 估算阅读时间（每分钟 300 字）
 */
function estimateReadTime(string $html): int {
    $text = strip_tags($html);
    $chars = mb_strlen($text);
    return max(1, (int)ceil($chars / 300));
}

$totalPages = $total > $per ? (int)ceil($total / $per) : 0;
?>

<style>
/* ===== 文章列表 — 横向卡片 ===== */
.articles-section {
    max-width: 1000px;
    margin: 0 auto;
    padding: 64px 24px;
}

.articles-section__title {
    font-family: var(--font-serif, serif);
    font-size: 1.875rem;
    color: var(--night-brown, #3E3640);
    text-align: center;
    margin-bottom: 40px;
}

/* ---------- 文章卡片列表 ---------- */
.article-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.article-card {
    display: flex;
    gap: 24px;
    padding: 20px;
    background: #fff;
    border-radius: var(--radius, 12px);
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(62,54,64,.06));
    transition: transform .3s ease, box-shadow .3s ease;
    text-decoration: none;
    color: inherit;
    overflow: hidden;
}

.article-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover, 0 12px 32px rgba(62,54,64,.12));
}

/* 左侧封面 */
.article-card__cover {
    flex-shrink: 0;
    width: 160px;
    height: 120px;
    border-radius: 10px;
    overflow: hidden;
}

.article-card__cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .5s ease;
}

.article-card:hover .article-card__cover img {
    transform: scale(1.06);
}

/* 无封面渐变占位 */
.article-card__cover--empty {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #DDB8B8, #A8C5DA);
    font-size: 2rem;
    opacity: .75;
}

/* 右侧文字区 */
.article-card__body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 2px 0;
}

.article-card__title {
    font-family: var(--font-serif, serif);
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.5;
    color: var(--night-brown, #3E3640);
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 8px;
    transition: color .2s ease;
}

.article-card:hover .article-card__title {
    color: var(--dusk-rose, #DDB8B8);
}

/* 摘要 80 字 + 渐变淡出 */
.article-card__excerpt {
    font-size: .875rem;
    color: var(--age-gray, #8E827F);
    line-height: 1.7;
    position: relative;
    max-height: 3.4em;
    overflow: hidden;
    -webkit-mask-image: linear-gradient(to bottom, black 50%, transparent);
    mask-image: linear-gradient(to bottom, black 50%);
}

/* 底部元数据 */
.article-card__meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: .78rem;
    color: var(--age-gray, #8E827F);
    margin-top: 10px;
}

.article-card__author {
    display: flex;
    align-items: center;
    gap: 6px;
}

.article-card__avatar {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #DDB8B8, #A8C5DA);
}

.article-card__avatar--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .65rem;
    color: #fff;
    font-weight: 600;
}

.article-card__dot {
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: var(--age-gray, #8E827F);
    opacity: .5;
}

/* ---------- 圆点分页 ---------- */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 40px;
}

.pagination__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(221,184,184,.3);
    transition: all .3s ease;
    text-decoration: none;
    display: block;
}

.pagination__dot:hover {
    background: rgba(221,184,184,.55);
    transform: scale(1.15);
}

.pagination__dot--active {
    width: 28px;
    border-radius: 5px;
    background: var(--dusk-rose, #DDB8B8);
}

/* ---------- 空状态 ---------- */
.articles-empty {
    text-align: center;
    padding: 80px 24px;
}

.articles-empty__icon {
    font-size: 3rem;
    margin-bottom: 16px;
    opacity: .6;
}

.articles-empty__text {
    font-size: 1.05rem;
    color: var(--age-gray, #8E827F);
    letter-spacing: .02em;
}

/* ---------- 响应式 ---------- */
@media (max-width: 640px) {
    .article-card {
        flex-direction: column;
        gap: 14px;
        padding: 16px;
    }
    .article-card__cover {
        width: 100%;
        height: 160px;
        border-radius: 8px;
    }
    .article-card__meta {
        flex-wrap: wrap;
        gap: 8px;
    }
}
</style>

<section class="articles-section pjax-fade-in">
    <div class="text-center mb-10">
        <h2 class="articles-section__title">文章</h2>
        <p class="text-sm text-[#8E827F]">静谧时光里的文字，值得细细品味</p>
    </div>

    <!-- 搜索 -->
    <div class="max-w-md mx-auto mb-10">
        <form method="GET" action="/articles" data-pjax class="relative">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="搜索文章..."
                   class="w-full px-5 py-3 bg-white/70 backdrop-blur-xl border border-[#E5E0DB]/50 rounded-full text-sm focus:outline-none focus:border-[#DDB8B8] focus:ring-2 focus:ring-[#DDB8B8]/20 transition-all placeholder:text-[#8E827F]/50">
            <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-[#8E827F] hover:text-[#DDB8B8] transition-colors">
                <i class="ph ph-magnifying-glass text-lg"></i>
            </button>
        </form>
        <?php if ($search): ?>
        <div class="text-center mt-3">
            <span class="text-xs text-[#8E827F]">搜索 "<?= htmlspecialchars($search) ?>" · <?= $total ?> 个结果</span>
            <a href="/articles" data-pjax class="text-xs text-[#DDB8B8] hover:text-[#B28B8B] ml-2">
                <i class="ph ph-x"></i> 清除
            </a>
        </div>
        <?php endif; ?>
    </div>

    <?php if (empty($articles)): ?>
        <div class="articles-empty">
            <div class="articles-empty__icon">📝</div>
            <p class="articles-empty__text">暂无文章</p>
        </div>
    <?php else: ?>
    <div class="article-list">
        <?php foreach ($articles as $article): ?>
            <?php
                $readTime = estimateReadTime($article['content'] ?? '');
                $summary  = mb_strimwidth(strip_tags($article['content'] ?? ''), 0, 80, '…');
                $author   = $article['author_name'] ?? '暮想';
                $avatar   = $article['author_avatar'] ?? '';
                $initial  = mb_substr($author, 0, 1);
                $dateStr  = date('Y-m-d', strtotime($article['created_at']));
            ?>
            <a href="/read?id=<?= (int)$article['id'] ?>" class="article-card">
                <!-- 封面 -->
                <div class="article-card__cover <?= empty($article['cover']) ? 'article-card__cover--empty' : '' ?>">
                    <?php if (!empty($article['cover'])): ?>
                        <img src="<?= htmlspecialchars($article['cover']) ?>"
                             alt="<?= htmlspecialchars($article['title']) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <span>📖</span>
                    <?php endif; ?>
                </div>

                <!-- 文字区 -->
                <div class="article-card__body">
                    <h3 class="article-card__title">
                        <?= htmlspecialchars($article['title']) ?>
                    </h3>
                    <p class="article-card__excerpt">
                        <?= htmlspecialchars($summary) ?>
                    </p>
                    <div class="article-card__meta">
                        <span class="article-card__author">
                            <?php if ($avatar): ?>
                                <img src="<?= htmlspecialchars($avatar) ?>" alt="" class="article-card__avatar">
                            <?php else: ?>
                                <span class="article-card__avatar article-card__avatar--placeholder"><?= htmlspecialchars($initial) ?></span>
                            <?php endif; ?>
                            <span><?= htmlspecialchars($author) ?></span>
                        </span>
                        <span class="article-card__dot"></span>
                        <span><?= $readTime ?> 分钟阅读</span>
                        <span class="article-card__dot"></span>
                        <span><?= $dateStr ?></span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="文章分页">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?><?= $search ? '&search='.urlencode($search) : '' ?>"
               class="pagination__dot <?= $p === $page ? 'pagination__dot--active' : '' ?>"
               aria-label="第 <?= $p ?> 页"
               <?= $p === $page ? 'aria-current="page"' : '' ?>></a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</section>
