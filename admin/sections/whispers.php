<?php
// 后台 - 悄悄话管理
$db = getDB();
$page = max(1, intval($_GET['page'] ?? 1));
$per = 15;
$offset = ($page - 1) * $per;

$total = $db->query("SELECT COUNT(*) FROM whispers")->fetchColumn();
$stmt = $db->prepare("SELECT w.*, u.username as author_name FROM whispers w LEFT JOIN users u ON w.author_id = u.id ORDER BY w.id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = ceil($total / $per);
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-serif text-[#3E3640]">悄悄话管理</h1>
</div>

<div class="bg-white rounded-xl overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#F9F7F4] text-left text-sm text-[#8E827F]">
            <tr><th class="px-5 py-3">内容预览</th><th class="px-5 py-3">作者</th><th class="px-5 py-3">时间</th><th class="px-5 py-3">操作</th></tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="4" class="px-5 py-10 text-center text-[#8E827F]">暂无悄悄话</td></tr>
            <?php else: foreach ($list as $item): ?>
            <tr class="border-t border-[#F5F2F0] hover:bg-[#F9F7F4]/50">
                <td class="px-5 py-3 text-sm max-w-xs truncate"><?= htmlspecialchars(mb_substr($item['content'], 0, 60)) ?></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($item['author_name'] ?? '匿名') ?></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($item['created_at']) ?></td>
                <td class="px-5 py-3">
                    <button class="text-[#D18B8B] hover:text-[#3E3640]" onclick="deleteItem('whispers', <?= $item['id'] ?>)">🗑️</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="flex justify-center gap-2 mt-6">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="/admin/whispers?page=<?= $i ?>"
       class="w-8 h-8 flex items-center justify-center rounded-full text-sm <?= $i === $page ? 'bg-[#DDB8B8] text-white' : 'bg-white text-[#3E3640] hover:bg-[#F5F2F0]' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>
