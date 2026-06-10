<?php
// 回忆时光轴 Section
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
[$page, $per, $offset] = getPagination(10);

$countStmt = $pdo->query("SELECT COUNT(*) FROM memories");
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT m.*, u.username AS author_name FROM memories m LEFT JOIN users u ON m.author_id = u.id ORDER BY m.event_time DESC LIMIT :per OFFSET :offset");
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
    <div class="relative">
        <!-- 中央时间轴线 -->
        <div class="absolute left-1/2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-[#DDB8B8]/20 via-[#DDB8B8]/40 to-[#DDB8B8]/20 transform -translate-x-1/2 hidden md:block"></div>
        <!-- 移动端时间轴线 -->
        <div class="absolute right-4 top-0 bottom-0 w-0.5 bg-gradient-to-b from-[#DDB8B8]/20 via-[#DDB8B8]/40 to-[#DDB8B8]/20 md:hidden"></div>

        <?php foreach ($memories as $idx => $memory): ?>
            <?php $isLeft = ($idx % 2 === 0); ?>
            <div class="relative flex items-start mb-12 scroll-reveal <?= $isLeft ? 'md:flex-row' : 'md:flex-row-reverse' ?> flex-row">
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
                            <?php if (!empty($memory['content'])): ?>
                                <p class="text-[#8E827F] text-sm leading-relaxed line-clamp-3 <?= $isLeft ? 'md:text-right' : '' ?>">
                                    <?= mb_strimwidth(strip_tags($memory['content']), 0, 120, '...') ?>
                                </p>
                            <?php endif; ?>
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

    <?php if ($total > $per): ?>
    <nav class="flex justify-center gap-2 mt-16">
        <?php $totalPages = ceil($total / $per); ?>
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>"
               class="w-8 h-8 flex items-center justify-center rounded-full text-xs transition-all <?= $p === $page ? 'bg-[#DDB8B8] text-white shadow-md' : 'bg-white/60 text-[#8E827F] hover:bg-[#DDB8B8]/20' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</section>

<!-- 滚动淡入动画 -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.scroll-reveal').forEach(el => observer.observe(el));
});
</script>
