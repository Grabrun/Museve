<?php
// 暮想 首页
$db = getDB();

// 读取引语设置
$quote_1 = $settings['quote_1'] ?? '时光会走远，记忆会永恒。';
$quote_2 = $settings['quote_2'] ?? '每一段回忆，都值得被温柔珍藏。';
$quote_3 = $settings['quote_3'] ?? '在薄暮时分，想起那些温暖的瞬间。';

// 头像
$avatar = $settings['home_avatar'] ?: $settings['site_avatar'] ?: '/resources/images/default-avatar.png';
$siteTitle = $settings['site_title'] ?? '暮想';
$siteSubtitle = $settings['site_subtitle'] ?? '在薄暮时分，温柔地想起。';

// 统计数据
$memoryCount = $db->query("SELECT COUNT(*) FROM memories")->fetchColumn();
$whisperCount = $db->query("SELECT COUNT(*) FROM whispers")->fetchColumn();
$articleCount = $db->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();

// 最近数据（用于下方可选展示）
$memories = $db->query("SELECT * FROM memories ORDER BY created_at DESC LIMIT 6")->fetchAll();
$whispers = $db->query("SELECT * FROM whispers ORDER BY created_at DESC LIMIT 3")->fetchAll();
$articles = $db->query("SELECT * FROM articles WHERE status='published' ORDER BY created_at DESC LIMIT 3")->fetchAll();
?>

<style>
/* ===== 首页专用样式 ===== */

/* 英雄区 */
.hero-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 52vh;
    padding: 60px 20px 40px;
    text-align: center;
    position: relative;
}

/* 头像发光环 */
.hero-avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 0 20px rgba(221,184,184,0.4);
    margin-bottom: 24px;
    transition: box-shadow 0.4s ease;
}

.hero-avatar:hover {
    box-shadow: 0 0 32px rgba(221,184,184,0.6);
}

/* 品牌标题 */
.hero-title {
    font-family: var(--font-handwrite);
    font-size: 2rem;
    color: var(--night-brown);
    margin-bottom: 8px;
    letter-spacing: 0.05em;
}

.hero-subtitle {
    font-size: 0.85rem;
    color: var(--age-gray);
    margin-bottom: 28px;
}

/* 打字机引语 */
.typewriter-wrapper {
    min-height: 2.4em;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 48px;
}

.typewriter-text {
    font-family: var(--font-serif);
    font-size: 1.05rem;
    color: var(--night-brown);
    line-height: 1.8;
    letter-spacing: 0.02em;
}

.typewriter-cursor {
    display: inline-block;
    width: 2px;
    height: 1.15em;
    background: var(--dusk-rose);
    margin-left: 3px;
    vertical-align: text-bottom;
    animation: cursor-blink 1s step-end infinite;
}

@keyframes cursor-blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}

/* 下箭头脉动 */
.scroll-indicator {
    position: absolute;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    cursor: pointer;
    color: var(--age-gray);
    font-size: 1.25rem;
    animation: pulse-down 2s ease-in-out infinite;
    transition: color 0.3s ease;
}

.scroll-indicator:hover {
    color: var(--dusk-rose);
}

@keyframes pulse-down {
    0%, 100% {
        transform: translateX(-50%) translateY(0);
        opacity: 0.5;
    }
    50% {
        transform: translateX(-50%) translateY(8px);
        opacity: 1;
    }
}

/* 入口卡片区 */
.entry-cards-section {
    padding: 0 0 48px;
}

.entry-cards-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    max-width: 800px;
    margin: 0 auto;
}

/* 毛玻璃入口卡片 */
.entry-card {
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.4);
    border-radius: 16px;
    padding: 28px 20px;
    text-align: center;
    box-shadow: 0 4px 20px rgba(62, 54, 64, 0.06);
    transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.35s cubic-bezier(0.22, 1, 0.36, 1);
    cursor: pointer;
    text-decoration: none;
    display: block;
    color: inherit;
}

.entry-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 32px rgba(62, 54, 64, 0.12);
    color: inherit;
}

.entry-card__icon {
    font-size: 1.8rem;
    color: var(--dusk-rose);
    margin-bottom: 12px;
    display: block;
}

.entry-card__title {
    font-family: var(--font-serif);
    font-size: 1rem;
    font-weight: 600;
    color: var(--night-brown);
    margin-bottom: 4px;
}

.entry-card__count {
    font-size: 0.8rem;
    color: var(--age-gray);
}

.entry-card__count-num {
    font-family: var(--font-sans);
    font-weight: 600;
    font-size: 1.1rem;
    color: var(--dusk-rose);
    margin-right: 2px;
}

/* 开发历程链接 */
.journey-link-wrapper {
    text-align: center;
    padding: 16px 0 32px;
}

.journey-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.85rem;
    color: var(--age-gray);
    text-decoration: none;
    padding-bottom: 4px;
    border-bottom: 1px solid rgba(142, 130, 127, 0.3);
    transition: color 0.3s ease, border-color 0.3s ease;
}

.journey-link:hover {
    color: var(--dusk-rose);
    border-color: var(--dusk-rose);
}

/* 响应式 */
@media (max-width: 640px) {
    .hero-section {
        min-height: 45vh;
        padding: 48px 16px 32px;
    }

    .hero-avatar {
        width: 72px;
        height: 72px;
    }

    .hero-title {
        font-size: 1.6rem;
    }

    .typewriter-text {
        font-size: 0.95rem;
    }

    .entry-cards-grid {
        grid-template-columns: 1fr;
        max-width: 320px;
        gap: 14px;
    }

    .entry-card {
        padding: 22px 16px;
    }
}
</style>

<!-- 英雄区 -->
<section class="hero-section">
    <img src="<?= htmlspecialchars($avatar) ?>" alt="头像" class="hero-avatar">
    <h1 class="hero-title"><?= htmlspecialchars($siteTitle) ?></h1>
    <p class="hero-subtitle"><?= htmlspecialchars($siteSubtitle) ?></p>

    <!-- 打字机引语 -->
    <div class="typewriter-wrapper">
        <span class="typewriter-text" id="typewriterText"></span>
        <span class="typewriter-cursor" id="typewriterCursor"></span>
    </div>

    <!-- 下箭头脉动指示器 -->
    <div class="scroll-indicator" id="scrollIndicator" title="向下探索">
        <i class="ph ph-caret-double-down"></i>
    </div>
</section>

<!-- 入口卡片 -->
<section class="entry-cards-section" id="entryCards">
    <div class="entry-cards-grid">
        <a href="/memories" data-pjax class="entry-card">
            <i class="ph ph-clock-counter-clockwise entry-card__icon"></i>
            <div class="entry-card__title">回忆</div>
            <div class="entry-card__count">
                <span class="entry-card__count-num" data-count="<?= $memoryCount ?>">0</span> 条回忆
            </div>
        </a>
        <a href="/whispers" data-pjax class="entry-card">
            <i class="ph ph-chat-circle-dots entry-card__icon"></i>
            <div class="entry-card__title">悄悄话</div>
            <div class="entry-card__count">
                <span class="entry-card__count-num" data-count="<?= $whisperCount ?>">0</span> 条悄悄话
            </div>
        </a>
        <a href="/articles" data-pjax class="entry-card">
            <i class="ph ph-article entry-card__icon"></i>
            <div class="entry-card__title">文章</div>
            <div class="entry-card__count">
                <span class="entry-card__count-num" data-count="<?= $articleCount ?>">0</span> 篇文章
            </div>
        </a>
    </div>
</section>

<!-- 开发历程链接 -->
<div class="journey-link-wrapper">
    <a href="/about" data-pjax class="journey-link">
        <i class="ph ph-git-commit"></i>
        开发历程
    </a>
</div>

<script>
(function() {
    // ===== 打字机引语 =====
    var quotes = <?= json_encode([$quote_1, $quote_2, $quote_3], JSON_UNESCAPED_UNICODE) ?>;
    var textEl = document.getElementById('typewriterText');
    var cursorEl = document.getElementById('typewriterCursor');
    var currentQuote = 0;
    var currentChar = 0;
    var isDeleting = false;
    var typingSpeed = 80;      // 打字速度 ms/字
    var deletingSpeed = 40;    // 删除速度 ms/字
    var pauseBeforeDelete = 2500;  // 显示完毕后停留
    var pauseBeforeType = 400;     // 删除完毕后停留
    var switchInterval = 8000;     // 8 秒切换引语

    function typeWriter() {
        var fullText = quotes[currentQuote];

        if (!isDeleting) {
            // 打字中
            currentChar++;
            textEl.textContent = fullText.substring(0, currentChar);

            if (currentChar >= fullText.length) {
                // 打字完毕，等待后删除
                setTimeout(function() {
                    isDeleting = true;
                    typeWriter();
                }, pauseBeforeDelete);
                return;
            }
            setTimeout(typeWriter, typingSpeed);
        } else {
            // 删除中
            currentChar--;
            textEl.textContent = fullText.substring(0, currentChar);

            if (currentChar <= 0) {
                // 删除完毕，切换到下一条
                isDeleting = false;
                currentQuote = (currentQuote + 1) % quotes.length;
                setTimeout(typeWriter, pauseBeforeType);
                return;
            }
            setTimeout(typeWriter, deletingSpeed);
        }
    }

    // 启动打字机
    if (quotes.length > 0 && quotes[0]) {
        setTimeout(typeWriter, 600);
    }

    // ===== 下箭头滚动 =====
    var scrollIndicator = document.getElementById('scrollIndicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', function() {
            var target = document.getElementById('entryCards');
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    }

    // ===== 统计数字递增动画 =====
    function animateCount(el) {
        var target = parseInt(el.getAttribute('data-count')) || 0;
        if (target === 0) { el.textContent = '0'; return; }
        var duration = 1200;
        var start = 0;
        var startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            // easeOutCubic
            var eased = 1 - Math.pow(1 - progress, 3);
            el.textContent = Math.floor(eased * target);
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                el.textContent = target;
            }
        }
        requestAnimationFrame(step);
    }

    // 使用 IntersectionObserver 触发计数动画
    var countEls = document.querySelectorAll('.entry-card__count-num');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animateCount(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        countEls.forEach(function(el) { observer.observe(el); });
    } else {
        countEls.forEach(animateCount);
    }
})();
</script>
