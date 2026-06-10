<?php
// 后台 - 操作日志
$db = getDB();
[$page, $per, $offset] = getPagination(20);
$action = trim($_GET['action'] ?? '');

$where = '';
$params = [];
if ($action) {
    $where = "WHERE l.action = :action";
    $params[':action'] = $action;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM logs l $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$stmt = $db->prepare("
    SELECT l.*, u.username, u.account
    FROM logs l
    LEFT JOIN users u ON l.user_id = u.id
    $where
    ORDER BY l.created_at DESC
    LIMIT :per OFFSET :offset
");
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

$totalPages = ceil($total / $per);

$actionLabels = [
    'login' => ['label' => '登录', 'color' => 'museve-green'],
    'logout' => ['label' => '退出', 'color' => 'museve-gray'],
    'create' => ['label' => '创建', 'color' => 'museve-blue'],
    'update' => ['label' => '更新', 'color' => 'museve-orange'],
    'delete' => ['label' => '删除', 'color' => 'museve-red'],
];

$typeLabels = [
    'memory' => '回忆',
    'whisper' => '悄悄话',
    'article' => '文章',
    'user' => '用户',
    'setting' => '设置',
];
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-serif text-museve-night">操作日志</h1>
        <p class="text-sm text-museve-gray mt-1">记录管理后台的所有操作</p>
    </div>
</div>

<!-- 筛选 -->
<div class="flex gap-2 mb-6 flex-wrap">
    <a href="/admin/logs" class="px-3 py-1.5 rounded-full text-xs transition-all <?= !$action ? 'bg-museve-night text-white' : 'bg-white text-museve-gray hover:bg-museve-haze' ?>">全部</a>
    <?php foreach ($actionLabels as $k => $v): ?>
    <a href="/admin/logs?action=<?= $k ?>" class="px-3 py-1.5 rounded-full text-xs transition-all <?= $action === $k ? 'bg-museve-night text-white' : 'bg-white text-museve-gray hover:bg-museve-haze' ?>"><?= $v['label'] ?></a>
    <?php endforeach; ?>
</div>

<!-- 日志表格 -->
<div class="bg-white rounded-xl overflow-hidden border border-[#E5E0DB]/50">
    <table class="w-full">
        <thead class="bg-[#F9F7F4] text-left text-xs text-museve-gray uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3">操作</th>
                <th class="px-5 py-3">目标</th>
                <th class="px-5 py-3">详情</th>
                <th class="px-5 py-3">用户</th>
                <th class="px-5 py-3">IP</th>
                <th class="px-5 py-3">时间</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($logs)): ?>
            <tr><td colspan="6" class="px-5 py-12 text-center text-museve-gray">暂无日志</td></tr>
            <?php else: foreach ($logs as $log): ?>
            <tr class="border-t border-[#F5F2F0] hover:bg-[#F9F7F4]/50">
                <td class="px-5 py-3">
                    <?php $al = $actionLabels[$log['action']] ?? ['label' => $log['action'], 'color' => 'museve-gray']; ?>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-<?= $al['color'] ?>/15 text-<?= $al['color'] ?>">
                        <?= $al['label'] ?>
                    </span>
                </td>
                <td class="px-5 py-3 text-sm">
                    <?= $typeLabels[$log['target_type']] ?? $log['target_type'] ?>
                    <?php if ($log['target_id']): ?>
                    <span class="text-museve-gray">#<?= $log['target_id'] ?></span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-sm text-museve-gray max-w-xs truncate"><?= htmlspecialchars($log['detail'] ?? '') ?></td>
                <td class="px-5 py-3 text-sm"><?= htmlspecialchars($log['username'] ?? $log['account'] ?? '-') ?></td>
                <td class="px-5 py-3 text-xs text-museve-gray font-mono"><?= htmlspecialchars($log['ip'] ?? '') ?></td>
                <td class="px-5 py-3 text-xs text-museve-gray"><?= date('m-d H:i', strtotime($log['created_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- 分页 -->
<?php if ($totalPages > 1): ?>
<div class="flex justify-center gap-2 mt-6">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
    <a href="?page=<?= $p ?><?= $action ? '&action='.urlencode($action) : '' ?>"
       class="w-8 h-8 flex items-center justify-center rounded-full text-xs transition-all <?= $p === $page ? 'bg-museve-rose text-white shadow-md' : 'bg-white text-museve-gray hover:bg-museve-haze' ?>">
        <?= $p ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>
