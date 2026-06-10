<?php
// 后台 - 文章管理
$db = getDB();
$page = max(1, intval($_GET['page'] ?? 1));
$per = 10;
$offset = ($page - 1) * $per;
$status = $_GET['status'] ?? '';

$where = '';
$params = [];
if ($status && in_array($status, ['draft','published','pending','archived','deleted'])) {
    $where = "WHERE a.status = ?";
    $params[] = $status;
}

$total = $db->prepare("SELECT COUNT(*) FROM articles a $where");
$total->execute($params);
$total = $total->fetchColumn();

$stmt = $db->prepare("SELECT a.*, u.username as author_name FROM articles a LEFT JOIN users u ON a.author_id = u.id $where ORDER BY a.id DESC LIMIT :limit OFFSET :offset");
foreach ($params as $i => $p) $stmt->bindValue($i + 1, $p);
$stmt->bindValue(':limit', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalPages = ceil($total / $per);

$statusColors = [
    'published' => 'bg-[#87A878]/20 text-[#87A878]',
    'draft' => 'bg-[#8E827F]/20 text-[#8E827F]',
    'pending' => 'bg-[#E0A96D]/20 text-[#E0A96D]',
    'archived' => 'bg-[#9BADB7]/20 text-[#9BADB7]',
    'deleted' => 'bg-[#D18B8B]/20 text-[#D18B8B]',
];
$statusLabels = ['draft'=>'草稿','published'=>'已发布','pending'=>'待审核','archived'=>'已归档','deleted'=>'已删除'];
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-serif text-[#3E3640]">文章管理</h1>
    <a href="/admin/articles/edit" class="bg-[#DDB8B8] hover:bg-[#B28B8B] text-white px-4 py-2 rounded-lg text-sm transition-colors">+ 新增文章</a>
</div>

<!-- 状态筛选 -->
<div class="flex gap-2 mb-4 flex-wrap">
    <a href="/admin/articles" class="px-3 py-1 rounded-full text-xs <?= !$status ? 'bg-[#3E3640] text-white' : 'bg-white text-[#8E827F] hover:bg-[#F5F2F0]' ?>">全部</a>
    <?php foreach ($statusLabels as $k => $v): ?>
    <a href="/admin/articles?status=<?= $k ?>" class="px-3 py-1 rounded-full text-xs <?= $status === $k ? 'bg-[#3E3640] text-white' : 'bg-white text-[#8E827F] hover:bg-[#F5F2F0]' ?>"><?= $v ?></a>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#F9F7F4] text-left text-sm text-[#8E827F]">
            <tr><th class="px-5 py-3">标题</th><th class="px-5 py-3">状态</th><th class="px-5 py-3">作者</th><th class="px-5 py-3">时间</th><th class="px-5 py-3">操作</th></tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="5" class="px-5 py-10 text-center text-[#8E827F]">暂无文章</td></tr>
            <?php else: foreach ($list as $item): ?>
            <tr class="border-t border-[#F5F2F0] hover:bg-[#F9F7F4]/50">
                <td class="px-5 py-3 text-sm"><?= htmlspecialchars($item['title']) ?></td>
                <td class="px-5 py-3"><span class="px-2 py-1 rounded-full text-xs <?= $statusColors[$item['status']] ?? '' ?>"><?= $statusLabels[$item['status']] ?? $item['status'] ?></span></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($item['author_name'] ?? '') ?></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($item['created_at']) ?></td>
                <td class="px-5 py-3">
                    <a href="/admin/articles/edit/<?= $item['id'] ?>" class="text-[#A8C5DA] hover:text-[#3E3640] mr-2">✏️</a>
                    <button class="text-[#D18B8B] hover:text-[#3E3640]" onclick="deleteItem('articles', <?= $item['id'] ?>)">🗑️</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="flex justify-center gap-2 mt-6">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="/admin/articles?page=<?= $i ?>&status=<?= urlencode($status) ?>"
       class="w-8 h-8 flex items-center justify-center rounded-full text-sm <?= $i === $page ? 'bg-[#DDB8B8] text-white' : 'bg-white text-[#3E3640] hover:bg-[#F5F2F0]' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>
