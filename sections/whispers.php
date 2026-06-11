<?php
// 悄悄话气泡流 Section
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();

// 首次加载 15 条
$_GET['per'] = 15;
[$page, $per, $offset] = getPagination(15);

$countStmt = $pdo->query("SELECT COUNT(*) FROM whispers");
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT w.*, u.username, u.avatar FROM whispers w LEFT JOIN users u ON w.author_id = u.id ORDER BY w.created_at DESC LIMIT :per OFFSET :offset");
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$whispers = $stmt->fetchAll();

// 品牌柔和色 (rgba)
$bubbleColors = [
    'rgba(221,184,184,0.15)',
    'rgba(168,197,218,0.15)',
    'rgba(135,168,120,0.12)',
    'rgba(224,169,109,0.12)',
];
?>

<style>
/* 悄悄话专用样式 */
.whisper-item {
    position: relative;
}
.whisper-bubble {
    position: relative;
    border-radius: 16px;
    padding: 16px 20px;
    border: none;
}
/* 左下小尾巴 */
.whisper-bubble::before {
    content: '';
    position: absolute;
    left: 16px;
    bottom: -8px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 8px 8px 0 0;
    border-color: inherit;
    border-right-color: transparent;
    border-bottom-color: transparent;
}
.whisper-bubble .whisper-name {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #3E3640;
    margin-bottom: 4px;
}
.whisper-bubble .whisper-content {
    font-family: 'Noto Serif SC', 'Songti SC', 'SimSun', serif;
    color: #3E3640;
    line-height: 1.75;
}
.whisper-time {
    color: #8E827F;
    font-size: 0.75rem;
}
.whisper-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    margin-top: 4px;
}
/* 奇数项桌面端左缩进 */
@media (min-width: 768px) {
    .whisper-item:nth-child(odd) {
        padding-left: 40px;
    }
}
/* 弹跳加载指示器 */
.loading-dots {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 24px 0;
}
.loading-dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #DDB8B8;
    display: inline-block;
    animation: dot-bounce 1.4s infinite ease-in-out both;
}
.loading-dots span:nth-child(1) { animation-delay: 0s; }
.loading-dots span:nth-child(2) { animation-delay: 0.16s; }
.loading-dots span:nth-child(3) { animation-delay: 0.32s; }
@keyframes dot-bounce {
    0%, 80%, 100% {
        transform: translateY(0);
        opacity: 0.5;
    }
    40% {
        transform: translateY(-10px);
        opacity: 1;
    }
}
.whisper-end {
    text-align: center;
    padding: 24px 0;
    color: #8E827F;
    font-size: 0.875rem;
}
</style>

<section class="max-w-2xl mx-auto px-4 py-16">
    <h2 class="text-3xl font-serif text-[#3E3640] text-center mb-12">悄悄话</h2>

    <?php if (empty($whispers)): ?>
        <div class="text-center py-20">
            <div class="text-5xl mb-4">💬</div>
            <p class="text-[#8E827F] text-lg">还没有悄悄话，留下第一条吧。</p>
        </div>
    <?php else: ?>
    <div id="whispers-list" class="flex flex-col gap-5" data-page="<?= $page ?>" data-total="<?= $total ?>" data-per="<?= $per ?>">
        <?php foreach ($whispers as $idx => $whisper): ?>
            <?php
                $bg = $bubbleColors[$idx % count($bubbleColors)];
            ?>
            <div class="whisper-item flex gap-3">
                <img src="<?= htmlspecialchars($whisper['avatar'] ?: '/resources/images/default-avatar.png') ?>"
                     alt="头像"
                     class="whisper-avatar"
                     onerror="this.src='/resources/images/default-avatar.png'">
                <div class="flex-1 min-w-0">
                    <div class="whisper-bubble" style="background: <?= $bg ?>; border-color: <?= str_replace(',0.15)', ',0.3)', str_replace(',0.12)', ',0.25)', $bg)) ?>">
                        <p class="whisper-name"><?= htmlspecialchars($whisper['signature'] ?: ($whisper['username'] ?? '匿名')) ?></p>
                        <p class="whisper-content"><?= nl2br(htmlspecialchars($whisper['content'] ?? '')) ?></p>
                    </div>
                    <time class="whisper-time block mt-2 ml-1"><?= htmlspecialchars($whisper['created_at'] ?? '') ?></time>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 底部 sentinel (IntersectionObserver 监听) -->
    <div id="whispers-sentinel" style="height: 1px;"></div>

    <!-- 加载指示器 (默认隐藏) -->
    <div id="whispers-loading" class="loading-dots" style="display: none;">
        <span></span><span></span><span></span>
    </div>

    <!-- 全部加载完毕 (默认隐藏) -->
    <div id="whispers-end" class="whisper-end" style="display: none;">没有更多了</div>

    <?php if ($total <= $per): ?>
    <script>
    (function() {
        var el = document.getElementById('whispers-end');
        if (el) el.style.display = 'block';
        var s = document.getElementById('whispers-sentinel');
        if (s) s.style.display = 'none';
    })();
    </script>
    <?php endif; ?>
    <?php endif; ?>
</section>

<script>
(function() {
    'use strict';

    var BUBBLE_COLORS = [
        { bg: 'rgba(221,184,184,0.15)', border: 'rgba(221,184,184,0.3)' },
        { bg: 'rgba(168,197,218,0.15)', border: 'rgba(168,197,218,0.3)' },
        { bg: 'rgba(135,168,120,0.12)', border: 'rgba(135,168,120,0.25)' },
        { bg: 'rgba(224,169,109,0.12)', border: 'rgba(224,169,109,0.25)' },
    ];

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function initWhispersScroll() {
        var list = document.getElementById('whispers-list');
        var sentinel = document.getElementById('whispers-sentinel');
        var loadingEl = document.getElementById('whispers-loading');
        var endEl = document.getElementById('whispers-end');

        if (!list || !sentinel) return;

        var currentPage = parseInt(list.dataset.page) || 1;
        var total = parseInt(list.dataset.total) || 0;
        var per = parseInt(list.dataset.per) || 15;
        var isLoading = false;
        var allLoaded = false;
        var observer = null;

        // 如果首次加载已经全部显示
        if (total <= per) {
            allLoaded = true;
            if (endEl) endEl.style.display = 'block';
            sentinel.style.display = 'none';
            return;
        }

        function appendWhisper(w, idx) {
            var color = BUBBLE_COLORS[idx % BUBBLE_COLORS.length];
            var avatar = escapeHtml(w.avatar || '/resources/images/default-avatar.svg');
            var username = escapeHtml(w.signature || w.username || '匿名');
            var content = escapeHtml(w.content || '').replace(/\n/g, '<br>');
            var time = escapeHtml(w.created_at || '');

            var div = document.createElement('div');
            div.className = 'whisper-item flex gap-3';
            div.innerHTML =
                '<img src="' + avatar + '" alt="头像" class="whisper-avatar" onerror="this.src=\'/resources/images/default-avatar.svg\'">' +
                '<div class="flex-1 min-w-0">' +
                    '<div class="whisper-bubble" style="background:' + color.bg + ';border-color:' + color.border + '">' +
                        '<p class="whisper-name">' + username + '</p>' +
                        '<p class="whisper-content">' + content + '</p>' +
                    '</div>' +
                    '<time class="whisper-time block mt-2 ml-1">' + time + '</time>' +
                '</div>';
            list.appendChild(div);
        }

        function loadNextPage() {
            if (isLoading || allLoaded) return;
            isLoading = true;

            if (loadingEl) loadingEl.style.display = 'flex';

            var nextPage = currentPage + 1;
            fetch('/api/whispers?page=' + nextPage + '&per=' + per)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.code === 200 && data.data && data.data.list && data.data.list.length > 0) {
                        var currentCount = list.children.length;
                        data.data.list.forEach(function(w, i) {
                            appendWhisper(w, currentCount + i);
                        });
                        currentPage = nextPage;
                        list.dataset.page = nextPage;

                        // 检查是否全部加载
                        if (data.data.list.length < per || (nextPage * per) >= total) {
                            allLoaded = true;
                            if (endEl) endEl.style.display = 'block';
                            sentinel.style.display = 'none';
                            if (observer) observer.disconnect();
                        }
                    } else {
                        allLoaded = true;
                        if (endEl) endEl.style.display = 'block';
                        sentinel.style.display = 'none';
                        if (observer) observer.disconnect();
                    }
                })
                .catch(function() {
                    // 失败时允许重试
                    console.error('[whispers] load failed, will retry on next scroll');
                })
                .finally(function() {
                    isLoading = false;
                    if (loadingEl) loadingEl.style.display = 'none';
                });
        }

        // IntersectionObserver 监听 sentinel
        if ('IntersectionObserver' in window) {
            observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting && !isLoading && !allLoaded) {
                        loadNextPage();
                    }
                });
            }, { rootMargin: '200px' });
            observer.observe(sentinel);
        } else {
            // 降级: 传统滚动监听
            var scrollHandler = function() {
                if (isLoading || allLoaded) return;
                var rect = sentinel.getBoundingClientRect();
                if (rect.top < window.innerHeight + 200) {
                    loadNextPage();
                }
            };
            window.addEventListener('scroll', scrollHandler, { passive: true });
        }
    }

    // Pjax 重新初始化
    function cleanup() {
        var sentinel = document.getElementById('whispers-sentinel');
        if (sentinel) sentinel.style.display = '';
        var loadingEl = document.getElementById('whispers-loading');
        if (loadingEl) loadingEl.style.display = 'none';
        var endEl = document.getElementById('whispers-end');
        if (endEl) endEl.style.display = 'none';
    }

    // 初始化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initWhispersScroll);
    } else {
        initWhispersScroll();
    }

    // Pjax 完成后重新初始化
    document.addEventListener('pjax:complete', function() {
        cleanup();
        // 延迟确保新 DOM 已渲染
        setTimeout(initWhispersScroll, 50);
    });
})();
</script>
