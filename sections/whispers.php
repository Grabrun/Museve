<?php
// 悄悄话气泡流 Section
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
[$page, $per, $offset] = getPagination();

$countStmt = $pdo->query("SELECT COUNT(*) FROM whispers");
$total = (int)$countStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT w.*, u.username, u.avatar FROM whispers w LEFT JOIN users u ON w.author_id = u.id ORDER BY w.created_at DESC LIMIT :per OFFSET :offset");
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$whispers = $stmt->fetchAll();

// 品牌柔和色
$bubbleColors = [
    'bg-[#DDB8B8]/20 border-[#DDB8B8]/30',
    'bg-[#A8C5DA]/20 border-[#A8C5DA]/30',
    'bg-[#87A878]/15 border-[#87A878]/25',
    'bg-[#E0A96D]/15 border-[#E0A96D]/25',
    'bg-[#9BADB7]/15 border-[#9BADB7]/25',
    'bg-[#D18B8B]/15 border-[#D18B8B]/25',
];
?>

<section class="max-w-2xl mx-auto px-4 py-16">
    <h2 class="text-3xl font-serif text-[#3E3640] text-center mb-12">悄悄话</h2>

    <?php if (empty($whispers)): ?>
        <div class="text-center py-20">
            <div class="text-5xl mb-4">💬</div>
            <p class="text-[#8E827F] text-lg">还没有悄悄话，留下第一条吧。</p>
        </div>
    <?php else: ?>
    <div id="whispers-list" class="space-y-5" data-page="<?= $page ?>">
        <?php foreach ($whispers as $idx => $whisper): ?>
            <?php
                $colorClass = $bubbleColors[$idx % count($bubbleColors)];
                $isOdd = ($idx % 2 !== 0);
            ?>
            <div class="whisper-item flex gap-3 <?= $isOdd ? 'ml-6 md:ml-12' : '' ?>">
                <!-- 头像 -->
                <img src="<?= htmlspecialchars($whisper['avatar'] ?: DEFAULT_AVATAR) ?>"
                     alt="头像"
                     class="w-9 h-9 rounded-full object-cover flex-shrink-0 mt-1 ring-2 ring-white shadow-sm"
                     onerror="this.src='<?= DEFAULT_AVATAR ?>'">
                <!-- 气泡 -->
                <div class="flex-1">
                    <div class="inline-block max-w-full rounded-2xl rounded-tl-sm px-5 py-3.5 border <?= $colorClass ?> shadow-sm">
                        <p class="text-sm font-semibold text-[#3E3640] mb-1"><?= htmlspecialchars($whisper['username'] ?? '匿名') ?></p>
                        <p class="font-serif text-[#3E3640] leading-relaxed"><?= nl2br(htmlspecialchars($whisper['content'] ?? '')) ?></p>
                    </div>
                    <time class="block text-xs text-[#8E827F] mt-1.5 ml-1"><?= htmlspecialchars($whisper['created_at'] ?? '') ?></time>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 加载更多 -->
    <?php if ($total > $page * $per): ?>
    <div class="text-center mt-10">
        <button id="load-more-whispers"
                onclick="loadMoreWhispers()"
                class="px-6 py-2.5 rounded-full bg-white/60 border border-[#DDB8B8]/30 text-[#8E827F] hover:bg-[#DDB8B8]/10 hover:text-[#3E3640] transition-all text-sm shadow-sm">
            加载更多
        </button>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</section>

<script>
async function loadMoreWhispers() {
    const list = document.getElementById('whispers-list');
    const btn = document.getElementById('load-more-whispers');
    const nextPage = parseInt(list.dataset.page) + 1;
    btn.disabled = true;
    btn.textContent = '加载中...';

    try {
        const resp = await fetch(`/api/whispers?page=${nextPage}`);
        const data = await resp.json();
        if (data.code === 200 && data.data.list.length > 0) {
            const colors = [
                'bg-[#DDB8B8]/20 border-[#DDB8B8]/30',
                'bg-[#A8C5DA]/20 border-[#A8C5DA]/30',
                'bg-[#87A878]/15 border-[#87A878]/25',
                'bg-[#E0A96D]/15 border-[#E0A96D]/25',
                'bg-[#9BADB7]/15 border-[#9BADB7]/25',
                'bg-[#D18B8B]/15 border-[#D18B8B]/25',
            ];
            const currentCount = list.children.length;
            data.data.list.forEach((w, i) => {
                const idx = currentCount + i;
                const colorClass = colors[idx % colors.length];
                const isOdd = idx % 2 !== 0;
                const avatar = w.avatar || '/resources/images/default-avatar.svg';
                const div = document.createElement('div');
                div.className = `whisper-item flex gap-3 ${isOdd ? 'ml-6 md:ml-12' : ''}`;
                div.innerHTML = `
                    <img src="${avatar}" alt="头像" class="w-9 h-9 rounded-full object-cover flex-shrink-0 mt-1 ring-2 ring-white shadow-sm" onerror="this.src='/resources/images/default-avatar.svg'">
                    <div class="flex-1">
                        <div class="inline-block max-w-full rounded-2xl rounded-tl-sm px-5 py-3.5 border ${colorClass} shadow-sm">
                            <p class="text-sm font-semibold text-[#3E3640] mb-1">${w.username || '匿名'}</p>
                            <p class="font-serif text-[#3E3640] leading-relaxed">${(w.content || '').replace(/\n/g, '<br>')}</p>
                        </div>
                        <time class="block text-xs text-[#8E827F] mt-1.5 ml-1">${w.created_at || ''}</time>
                    </div>
                `;
                list.appendChild(div);
            });
            list.dataset.page = nextPage;
            btn.textContent = '加载更多';
            btn.disabled = false;
            if (data.data.list.length < data.data.per) btn.remove();
        } else {
            btn.textContent = '没有更多了';
        }
    } catch (e) {
        btn.textContent = '加载失败，点击重试';
        btn.disabled = false;
    }
}
</script>
