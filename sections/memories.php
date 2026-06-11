<?php
// 回忆时光轴 Section — 懒加载版
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
$per = 10;

$countStmt = $pdo->query("SELECT COUNT(*) FROM memories");
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT m.*, u.username AS author_name FROM memories m LEFT JOIN users u ON m.author_id = u.id ORDER BY m.event_time DESC LIMIT :per");
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->execute();
$memories = $stmt->fetchAll();
?>

<section class="max-w-4xl mx-auto px-4 py-16">
    <div class="text-center mb-16">
        <h2 class="text-3xl font-serif text-[#3E3640] mb-2">回忆时光轴</h2>
        <p class="text-sm text-[#8E827F]">每一段时光，都是不可复制的珍藏</p>
    </div>

    <?php if (empty($memories)): ?>
        <div class="text-center py-24">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-[#DDB8B8]/10 mb-6">
                <i class="ph ph-clock-counter-clockwise text-4xl text-[#DDB8B8]"></i>
            </div>
            <p class="text-[#8E827F] text-lg mb-2">还没有回忆</p>
            <p class="text-[#8E827F]/60 text-sm">快去后台创建第一段时光吧</p>
        </div>
    <?php else: ?>
    <div id="memories-timeline" class="relative" data-page="1" data-total="<?= $total ?>" data-per="<?= $per ?>">
        <!-- 中央时间轴线 -->
        <div class="absolute left-1/2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-[#DDB8B8]/20 via-[#DDB8B8]/40 to-[#DDB8B8]/20 transform -translate-x-1/2 hidden md:block"></div>
        <!-- 移动端时间轴线 -->
        <div class="absolute right-4 top-0 bottom-0 w-0.5 bg-gradient-to-b from-[#DDB8B8]/20 via-[#DDB8B8]/40 to-[#DDB8B8]/20 md:hidden"></div>

        <?php foreach ($memories as $idx => $memory): ?>
            <?php $isLeft = ($idx % 2 === 0); ?>
            <div class="memory-item relative flex items-start mb-12 scroll-reveal <?= $isLeft ? 'md:flex-row' : 'md:flex-row-reverse' ?> flex-row">
                <!-- 时间轴节点 -->
                <div class="absolute right-2 md:left-1/2 w-4 h-4 rounded-full bg-[#A8C5DA] border-[3px] border-white shadow-md transform translate-x-1/2 md:-translate-x-1/2 z-10 mt-6 md:mt-8 transition-colors hover:bg-[#DDB8B8]"></div>

                <!-- 时间标签 (桌面端) -->
                <div class="hidden md:block absolute left-1/2 transform -translate-x-1/2 mt-[-8px]">
                    <span class="inline-block bg-[#A8C5DA]/20 text-[#A8C5DA] text-xs px-3 py-1 rounded-full font-medium">
                        <?= date('Y.m', strtotime($memory['event_time'])) ?>
                    </span>
                </div>

                <!-- 卡片 -->
                <div class="ml-10 md:ml-0 <?= $isLeft ? 'md:w-[calc(50%-2.5rem)] md:pr-6 md:text-right' : 'md:w-[calc(50%-2.5rem)] md:pl-6 md:text-left' ?> w-full">
                    <div class="group bg-white/60 backdrop-blur-xl rounded-2xl overflow-hidden shadow-[0_4px_20px_rgba(62,54,64,0.06)] border border-white/60 hover:shadow-[0_12px_32px_rgba(62,54,64,0.12)] hover:-translate-y-1 transition-all duration-300">
                        <?php if (!empty($memory['image'])): ?>
                            <div class="overflow-hidden">
                                <img src="<?= htmlspecialchars($memory['image']) ?>"
                                     alt="<?= htmlspecialchars($memory['title']) ?>"
                                     loading="lazy"
                                     class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500">
                            </div>
                        <?php endif; ?>
                        <div class="p-5">
                            <!-- 移动端时间标签 -->
                            <time class="md:hidden inline-block bg-[#A8C5DA]/15 text-[#A8C5DA] text-xs px-2 py-0.5 rounded-full mb-2">
                                <?= date('Y.m.d', strtotime($memory['event_time'])) ?>
                            </time>
                            <h3 class="text-lg font-serif font-medium text-[#3E3640] mb-2 group-hover:text-[#DDB8B8] transition-colors">
                                <?= htmlspecialchars($memory['title']) ?>
                            </h3>
                            <?php if (!empty($memory['author_name'])): ?>
                                <div class="flex items-center gap-2 mt-3 <?= $isLeft ? 'md:justify-end' : '' ?>">
                                    <div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#DDB8B8] to-[#A8C5DA]"></div>
                                    <span class="text-xs text-[#8E827F]"><?= htmlspecialchars($memory['author_name']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 加载指示器 -->
    <div id="memories-loading" class="loading-dots" style="display: none;">
        <span></span><span></span><span></span>
    </div>

    <!-- 底部 sentinel -->
    <div id="memories-sentinel" style="height: 1px;"></div>

    <!-- 全部加载完毕 -->
    <div id="memories-end" class="text-center py-8 text-sm text-[#8E827F]" style="display: none;">已经到底了</div>
    <?php endif; ?>
</section>

<style>
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
    0%, 80%, 100% { transform: translateY(0); opacity: 0.5; }
    40% { transform: translateY(-10px); opacity: 1; }
}
</style>

<script>
(function() {
    'use strict';

    // 初始条目的滚动淡入动画
    var revealObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.memory-item.scroll-reveal:not(.revealed)').forEach(function(el) {
        revealObserver.observe(el);
    });

    var timeline = document.getElementById('memories-timeline');
    var sentinel = document.getElementById('memories-sentinel');
    var loadingEl = document.getElementById('memories-loading');
    var endEl = document.getElementById('memories-end');

    if (!timeline || !sentinel) return;

    var currentPage = parseInt(timeline.dataset.page) || 1;
    var total = parseInt(timeline.dataset.total) || 0;
    var per = parseInt(timeline.dataset.per) || 10;
    var isLoading = false;
    var allLoaded = (total <= per);
    var observer = null;

    if (allLoaded) {
        if (endEl) endEl.style.display = 'block';
        sentinel.style.display = 'none';
        return;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function appendMemory(item, idx) {
        var isLeft = (idx % 2 === 0);
        var imageHtml = item.image
            ? '<div class="overflow-hidden"><img src="' + escapeHtml(item.image) + '" alt="' + escapeHtml(item.title) + '" loading="lazy" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500"></div>'
            : '';
        var authorHtml = item.author_name
            ? '<div class="flex items-center gap-2 mt-3' + (isLeft ? ' md:justify-end' : '') + '"><div class="w-5 h-5 rounded-full bg-gradient-to-br from-[#DDB8B8] to-[#A8C5DA]"></div><span class="text-xs text-[#8E827F]">' + escapeHtml(item.author_name) + '</span></div>'
            : '';
        var dateLabel = new Date(item.event_time).toISOString ? item.event_time.substring(0, 7).replace('-', '.') : item.event_time;
        var dateLabelMobile = new Date(item.event_time).toISOString ? item.event_time.substring(0, 10).replace(/-/g, '.') : item.event_time;

        var div = document.createElement('div');
        div.className = 'memory-item relative flex items-start mb-12 scroll-reveal ' + (isLeft ? 'md:flex-row' : 'md:flex-row-reverse') + ' flex-row';
        div.innerHTML =
            '<div class="absolute right-2 md:left-1/2 w-4 h-4 rounded-full bg-[#A8C5DA] border-[3px] border-white shadow-md transform translate-x-1/2 md:-translate-x-1/2 z-10 mt-6 md:mt-8"></div>' +
            '<div class="hidden md:block absolute left-1/2 transform -translate-x-1/2 mt-[-8px]"><span class="inline-block bg-[#A8C5DA]/20 text-[#A8C5DA] text-xs px-3 py-1 rounded-full font-medium">' + dateLabel + '</span></div>' +
            '<div class="ml-10 md:ml-0 ' + (isLeft ? 'md:w-[calc(50%-2.5rem)] md:pr-6 md:text-right' : 'md:w-[calc(50%-2.5rem)] md:pl-6 md:text-left') + ' w-full">' +
                '<div class="group bg-white/60 backdrop-blur-xl rounded-2xl overflow-hidden shadow-[0_4px_20px_rgba(62,54,64,0.06)] border border-white/60 hover:shadow-[0_12px_32px_rgba(62,54,64,0.12)] hover:-translate-y-1 transition-all duration-300">' +
                    imageHtml +
                    '<div class="p-5">' +
                        '<time class="md:hidden inline-block bg-[#A8C5DA]/15 text-[#A8C5DA] text-xs px-2 py-0.5 rounded-full mb-2">' + dateLabelMobile + '</time>' +
                        '<h3 class="text-lg font-serif font-medium text-[#3E3640] mb-2 group-hover:text-[#DDB8B8] transition-colors">' + escapeHtml(item.title) + '</h3>' +
                        authorHtml +
                    '</div>' +
                '</div>' +
            '</div>';

        timeline.appendChild(div);

        // trigger scroll-reveal animation
        requestAnimationFrame(function() {
            div.classList.add('revealed');
        });
    }

    function loadNextPage() {
        if (isLoading || allLoaded) return;
        isLoading = true;
        if (loadingEl) loadingEl.style.display = 'flex';

        var nextPage = currentPage + 1;
        fetch('/api/memories?page=' + nextPage + '&per=' + per)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.code === 200 && data.data && data.data.list && data.data.list.length > 0) {
                    var currentCount = timeline.querySelectorAll('.memory-item').length;
                    data.data.list.forEach(function(item, i) {
                        appendMemory(item, currentCount + i);
                    });
                    currentPage = nextPage;
                    timeline.dataset.page = nextPage;

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
            .catch(function() {})
            .finally(function() {
                isLoading = false;
                if (loadingEl) loadingEl.style.display = 'none';
            });
    }

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
        var scrollHandler = function() {
            if (isLoading || allLoaded) return;
            var rect = sentinel.getBoundingClientRect();
            if (rect.top < window.innerHeight + 200) {
                loadNextPage();
            }
        };
        window.addEventListener('scroll', scrollHandler, { passive: true });
    }
})();
</script>
