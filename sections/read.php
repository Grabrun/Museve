<?php
// 文章详情 Section
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
$id = getRouteId();

if (!$id) {
    if (file_exists(__DIR__ . '/404.php')) require __DIR__ . '/404.php';
    exit;
}

$stmt = $pdo->prepare("SELECT a.*, u.username AS author_name, u.avatar AS author_avatar FROM articles a LEFT JOIN users u ON a.author_id = u.id WHERE a.id = :id AND a.status = 'published'");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$article = $stmt->fetch();

if (!$article) {
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

// 阅读时间（每分钟 300 字）
$textLength = mb_strlen(strip_tags($article['content'] ?? ''));
$readTime = max(1, (int)ceil($textLength / 300));

$author = $article['author_name'] ?? '暮想';
$avatar = $article['author_avatar'] ?? '';
$initial = mb_substr($author, 0, 1);
?>

<style>
/* ===== 文章详情 ===== */
.read-article {
    max-width: 720px;
    margin: 0 auto;
    padding: 64px 24px 120px;
    position: relative;
    z-index: 1;
}

/* 背景纹理噪点 — 由 .bg-noise 类提供，已在全局 style.css 定义 */

/* ---------- 封面图 ---------- */
.read-cover {
    width: 100%;
    height: auto;
    max-height: 400px;
    object-fit: cover;
    border-radius: var(--radius-lg, 24px);
    margin-bottom: 40px;
    box-shadow: var(--shadow-lg, 0 8px 30px rgba(62,54,64,.15));
}

/* ---------- 大标题 ---------- */
.read-header__title {
    font-family: var(--font-serif, serif);
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1.35;
    color: var(--night-brown, #3E3640);
    margin-bottom: 20px;
    letter-spacing: .01em;
}

/* ---------- 作者信息 + 分割线 ---------- */
.read-author {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 28px;
    margin-bottom: 36px;
    border-bottom: 1px solid rgba(62,54,64,.1);
}

.read-author__avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #DDB8B8, #A8C5DA);
    flex-shrink: 0;
}

.read-author__avatar--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .85rem;
    color: #fff;
    font-weight: 600;
}

.read-author__info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.read-author__name {
    font-size: .9rem;
    font-weight: 600;
    color: var(--night-brown, #3E3640);
}

.read-author__meta {
    font-size: .78rem;
    color: var(--age-gray, #8E827F);
    display: flex;
    align-items: center;
    gap: 8px;
}

.read-author__dot {
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: var(--age-gray, #8E827F);
    opacity: .5;
}

/* ---------- 正文排版 ---------- */
.read-content {
    font-family: var(--font-serif, serif);
    font-size: 1.125rem;
    line-height: 1.8;
    color: var(--night-brown, #3E3640);
}

.read-content h2,
.read-content h3,
.read-content h4 {
    font-family: var(--font-serif, serif);
    color: var(--night-brown, #3E3640);
    margin-top: 2em;
    margin-bottom: .75em;
    line-height: 1.4;
}

.read-content h2 { font-size: 1.5rem; }
.read-content h3 { font-size: 1.25rem; }

.read-content p {
    margin-bottom: 1.25em;
}

.read-content a {
    color: var(--dusk-rose, #DDB8B8);
    text-decoration: underline;
    text-underline-offset: 3px;
    text-decoration-color: rgba(221,184,184,.4);
    transition: text-decoration-color .2s ease;
}

.read-content a:hover {
    text-decoration-color: var(--dusk-rose, #DDB8B8);
}

.read-content img {
    max-width: 100%;
    border-radius: var(--radius-md, 16px);
    margin: 1.5em 0;
}

/* ---------- 引用块 ---------- */
.read-content blockquote {
    border-left: 3px solid var(--dusk-rose, #DDB8B8);
    background: rgba(245,242,240,.7);
    padding: 16px 20px;
    margin: 1.5em 0;
    border-radius: 0 var(--radius-sm, 8px) var(--radius-sm, 8px) 0;
    color: var(--age-gray, #8E827F);
    font-style: italic;
}

.read-content blockquote p:last-child {
    margin-bottom: 0;
}

/* ---------- 代码块 ---------- */
.read-content pre {
    background: var(--haze-gray, #F5F2F0);
    border-radius: var(--radius-sm, 8px);
    padding: 20px 24px;
    margin: 1.5em 0;
    overflow-x: auto;
    font-size: .9rem;
    line-height: 1.65;
    font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace;
}

.read-content code {
    font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace;
    font-size: .88em;
}

.read-content :not(pre) > code {
    background: rgba(221,184,184,.15);
    padding: 2px 6px;
    border-radius: 4px;
    color: var(--night-brown, #3E3640);
}

/* ---------- 上一篇/下一篇 ---------- */

/* 桌面端：固定侧边悬浮按钮 */
.read-nav-desktop {
    position: fixed;
    top: 50%;
    left: 0;
    right: 0;
    transform: translateY(-50%);
    pointer-events: none;
    z-index: 50;
    display: flex;
    justify-content: space-between;
    padding: 0 max(24px, calc((100vw - 720px) / 2 - 80px));
}

.read-nav-btn {
    pointer-events: auto;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: rgba(255,255,255,.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(62,54,64,.08);
    border-radius: var(--radius-sm, 8px);
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(62,54,64,.06));
    text-decoration: none;
    color: var(--night-brown, #3E3640);
    font-size: .82rem;
    max-width: 200px;
    transition: all .3s ease;
    opacity: 0;
    transform: translateX(0);
}

.read-nav-btn--prev { transform: translateX(-12px); }
.read-nav-btn--next { flex-direction: row-reverse; transform: translateX(12px); }

.read-nav-btn:hover {
    box-shadow: var(--shadow-md, 0 4px 12px rgba(62,54,64,.1));
    color: var(--dusk-rose, #DDB8B8);
}

.read-nav-btn__arrow {
    font-size: 1.1rem;
    color: var(--dusk-rose, #DDB8B8);
    flex-shrink: 0;
}

.read-nav-btn__title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

/* JS 触发动画 */
.read-nav-btn.is-visible {
    opacity: 1;
    transform: translateX(0);
}

/* 移动端：底部横条 */
.read-nav-mobile {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-top: 1px solid rgba(62,54,64,.08);
    padding: 12px 20px;
    padding-bottom: max(12px, env(safe-area-inset-bottom));
    z-index: 50;
}

.read-nav-mobile__inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    max-width: 720px;
    margin: 0 auto;
}

.read-nav-mobile a {
    display: flex;
    flex-direction: column;
    text-decoration: none;
    color: var(--night-brown, #3E3640);
    font-size: .78rem;
    max-width: 45%;
    transition: color .2s ease;
}

.read-nav-mobile a:hover {
    color: var(--dusk-rose, #DDB8B8);
}

.read-nav-mobile a:last-child {
    text-align: right;
    margin-left: auto;
}

.read-nav-mobile__label {
    font-size: .68rem;
    color: var(--age-gray, #8E827F);
    margin-bottom: 2px;
}

.read-nav-mobile__title {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-weight: 500;
}

/* ---------- 响应式 ---------- */
@media (max-width: 768px) {
    .read-article {
        padding: 40px 20px 100px;
    }
    .read-header__title {
        font-size: 1.75rem;
    }
    .read-content {
        font-size: 1.05rem;
    }
    .read-nav-desktop {
        display: none;
    }
    .read-nav-mobile {
        display: block;
    }
}

@media (min-width: 769px) {
    .read-nav-mobile {
        display: none !important;
    }
}
</style>

<article class="read-article bg-noise pjax-fade-in">
    <!-- 封面 -->
    <?php if (!empty($article['cover'])): ?>
        <img src="<?= htmlspecialchars($article['cover']) ?>"
             alt="<?= htmlspecialchars($article['title']) ?>"
             class="read-cover">
    <?php endif; ?>

    <!-- 标题 -->
    <header>
        <h1 class="read-header__title">
            <?= htmlspecialchars($article['title']) ?>
        </h1>

        <!-- 作者信息 + 分割线 -->
        <div class="read-author">
            <?php if ($avatar): ?>
                <img src="<?= htmlspecialchars($avatar) ?>" alt="" class="read-author__avatar">
            <?php else: ?>
                <span class="read-author__avatar read-author__avatar--placeholder"><?= htmlspecialchars($initial) ?></span>
            <?php endif; ?>
            <div class="read-author__info">
                <span class="read-author__name"><?= htmlspecialchars($author) ?></span>
                <span class="read-author__meta">
                    <time><?= htmlspecialchars(date('Y年n月j日', strtotime($article['created_at']))) ?></time>
                    <span class="read-author__dot"></span>
                    <span><?= $readTime ?> 分钟阅读</span>
                </span>
            </div>
        </div>
    </header>

    <!-- 正文 -->
    <div class="read-content">
        <?= $article['content'] ?>
    </div>
</article>

<!-- 桌面端：固定侧边悬浮导航 -->
<nav class="read-nav-desktop" aria-label="前后文章导航">
    <?php if ($prevArticle): ?>
        <a href="/read?id=<?= (int)$prevArticle['id'] ?>" class="read-nav-btn read-nav-btn--prev">
            <span class="read-nav-btn__arrow">←</span>
            <span class="read-nav-btn__title"><?= htmlspecialchars($prevArticle['title']) ?></span>
        </a>
    <?php else: ?>
        <div></div>
    <?php endif; ?>

    <?php if ($nextArticle): ?>
        <a href="/read?id=<?= (int)$nextArticle['id'] ?>" class="read-nav-btn read-nav-btn--next">
            <span class="read-nav-btn__arrow">→</span>
            <span class="read-nav-btn__title"><?= htmlspecialchars($nextArticle['title']) ?></span>
        </a>
    <?php else: ?>
        <div></div>
    <?php endif; ?>
</nav>

<!-- 移动端：底部固定横条 -->
<nav class="read-nav-mobile" aria-label="前后文章导航">
    <div class="read-nav-mobile__inner">
        <?php if ($prevArticle): ?>
            <a href="/read?id=<?= (int)$prevArticle['id'] ?>">
                <span class="read-nav-mobile__label">← 上一篇</span>
                <span class="read-nav-mobile__title"><?= htmlspecialchars($prevArticle['title']) ?></span>
            </a>
        <?php endif; ?>

        <?php if ($nextArticle): ?>
            <a href="/read?id=<?= (int)$nextArticle['id'] ?>">
                <span class="read-nav-mobile__label">下一篇 →</span>
                <span class="read-nav-mobile__title"><?= htmlspecialchars($nextArticle['title']) ?></span>
            </a>
        <?php endif; ?>
    </div>
</nav>

<script>
// 侧边导航按钮渐入动画（滚动到正文底部区域时触发）
(function() {
    var btns = document.querySelectorAll('.read-nav-btn');
    if (!btns.length) return;

    var triggered = false;
    function showBtns() {
        if (triggered) return;
        var scrollTop = window.scrollY || document.documentElement.scrollTop;
        var docHeight = document.documentElement.scrollHeight - window.innerHeight;
        if (scrollTop > docHeight * 0.15 || scrollTop > 400) {
            triggered = true;
            btns.forEach(function(b) { b.classList.add('is-visible'); });
            window.removeEventListener('scroll', showBtns);
        }
    }
    window.addEventListener('scroll', showBtns, { passive: true });
    showBtns();
})();
</script>
