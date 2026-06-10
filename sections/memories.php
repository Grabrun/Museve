<?php
// 回忆时光轴 Section
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
[$page, $per, $offset] = getPagination();

$countStmt = $pdo->query("SELECT COUNT(*) FROM memories");
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM memories ORDER BY event_time DESC LIMIT :per OFFSET :offset");
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$memories = $stmt->fetchAll();
?>

<section class="max-w-4xl mx-auto px-4 py-16">
    <h2 class="text-3xl font-serif text-[#3E3640] text-center mb-12">回忆时光轴</h2>

    <?php if (empty($memories)): ?>
        <div class="text-center py-20">
            <div class="text-5xl mb-4">🕰️</div>
            <p class="text-[#8E827F] text-lg">还没有回忆，快去创建第一段吧。</p>
        </div>
    <?php else: ?>
    <div class="relative">
        <!-- 中央时间轴线 -->
        <div class="absolute left-1/2 md:left-1/2 top-0 bottom-0 w-0.5 bg-[#DDB8B8]/30 transform -translate-x-1/2 hidden md:block"></div>
        <!-- 移动端时间轴线 -->
        <div class="absolute right-4 top-0 bottom-0 w-0.5 bg-[#DDB8B8]/30 md:hidden"></div>

        <?php foreach ($memories as $idx => $memory): ?>
            <?php $isLeft = ($idx % 2 === 0); ?>
            <div class="relative flex items-start mb-10 <?= $isLeft ? 'md:flex-row' : 'md:flex-row-reverse' ?> flex-row">
                <!-- 时间轴节点 -->
                <div class="absolute right-2 md:left-1/2 w-4 h-4 rounded-full bg-[#A8C5DA] border-4 border-white shadow transform translate-x-1/2 md:-translate-x-1/2 z-10 mt-6 md:mt-8"></div>

                <!-- 卡片 -->
                <div class="ml-10 md:ml-0 <?= $isLeft ? 'md:w-[calc(50%-2rem)] md:pr-8 md:text-right' : 'md:w-[calc(50%-2rem)] md:pl-8 md:text-left' ?> w-full">
                    <div class="bg-white/60 backdrop-blur-md rounded-xl p-5 shadow-md border border-white/50 hover:shadow-lg transition-shadow">
                        <?php if (!empty($memory['image'])): ?>
                            <img src="<?= htmlspecialchars($memory['image']) ?>"
                                 alt="<?= htmlspecialchars($memory['title']) ?>"
                                 loading="lazy"
                                 class="w-full h-48 object-cover rounded-lg mb-3">
                        <?php endif; ?>
                        <h3 class="text-lg font-serif text-[#3E3640] mb-1"><?= htmlspecialchars($memory['title']) ?></h3>
                        <time class="text-sm text-[#8E827F] block mb-2"><?= htmlspecialchars($memory['event_time'] ?? '') ?></time>
                        <p class="text-[#5A5055] text-sm leading-relaxed line-clamp-3"><?= mb_strimwidth(strip_tags($memory['content'] ?? ''), 0, 120, '...') ?></p>
                    </div>
                </div>
            </div>
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
