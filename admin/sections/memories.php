<?php
// 后台 - 回忆管理
$db = getDB();
$page = max(1, intval($_GET['page'] ?? 1));
$per = 10;
$offset = ($page - 1) * $per;
$search = trim($_GET['search'] ?? '');

// 默认值
$list = [];
$total = 0;
$totalPages = 0;

try {
    $where = '';
    $params = [];
    if ($search) {
        $where = "WHERE title LIKE ?";
        $params[] = "%$search%";
    }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM memories $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT m.*, u.username as author_name FROM memories m LEFT JOIN users u ON m.author_id = u.id $where ORDER BY m.id DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $i => $p) $stmt->bindValue($i + 1, $p);
    $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($total / $per);
} catch (PDOException $e) {
    error_log('[Museve] 回忆管理查询失败: ' . $e->getMessage());
}
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-serif text-[#3E3640]">回忆管理</h1>
    <button onclick="openModal('addMemoryModal')" class="bg-[#DDB8B8] hover:bg-[#B28B8B] text-white px-4 py-2 rounded-lg text-sm transition-colors">+ 新增回忆</button>
</div>

<!-- 搜索 -->
<div class="mb-4">
    <input type="text" id="memorySearch" placeholder="搜索回忆..." value="<?= htmlspecialchars($search) ?>"
           class="w-full max-w-sm px-4 py-2 bg-white border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]"
           onkeyup="if(event.key==='Enter')location.href='/admin/memories?search='+this.value">
</div>

<!-- 表格 -->
<div class="bg-white rounded-xl overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#F9F7F4] text-left text-sm text-[#8E827F]">
            <tr><th class="px-5 py-3">缩略图</th><th class="px-5 py-3">标题</th><th class="px-5 py-3">事件时间</th><th class="px-5 py-3">创建者</th><th class="px-5 py-3">操作</th></tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="5" class="px-5 py-10 text-center text-[#8E827F]">暂无回忆</td></tr>
            <?php else: foreach ($list as $item): ?>
            <tr class="border-t border-[#F5F2F0] hover:bg-[#F9F7F4]/50">
                <td class="px-5 py-3">
                    <?php if ($item['image']): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" class="w-12 h-12 object-cover rounded-lg">
                    <?php else: ?>
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-[#DDB8B8] to-[#A8C5DA]"></div>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-sm"><?= htmlspecialchars($item['title']) ?></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($item['event_time']) ?></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($item['author_name'] ?? '') ?></td>
                <td class="px-5 py-3">
                    <button class="text-[#A8C5DA] hover:text-[#3E3640] mr-2" onclick="editMemory(<?= $item['id'] ?>)">✏️</button>
                    <button class="text-[#D18B8B] hover:text-[#3E3640]" onclick="deleteItem('memories', <?= $item['id'] ?>)">🗑️</button>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- 分页 -->
<?php if ($totalPages > 1): ?>
<div class="flex justify-center gap-2 mt-6">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="/admin/memories?page=<?= $i ?>&search=<?= urlencode($search) ?>"
       class="w-8 h-8 flex items-center justify-center rounded-full text-sm <?= $i === $page ? 'bg-[#DDB8B8] text-white' : 'bg-white text-[#3E3640] hover:bg-[#F5F2F0]' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function editMemory(id) { /* TODO: 编辑弹窗 */ }
</script>
