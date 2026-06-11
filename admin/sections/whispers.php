<?php
// 后台 - 悄悄话管理
$db = getDB();
$page = max(1, intval($_GET['page'] ?? 1));
$per = 15;
$offset = ($page - 1) * $per;
$search = trim($_GET['search'] ?? '');

$list = [];
$total = 0;
$totalPages = 0;

try {
    $where = '';
    $params = [];
    if ($search) {
        $where = "WHERE w.content LIKE ?";
        $params[] = "%$search%";
    }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM whispers w $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT w.*, u.username as author_name FROM whispers w LEFT JOIN users u ON w.author_id = u.id $where ORDER BY w.created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $i => $p) $stmt->bindValue($i + 1, $p);
    $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($total / $per);
} catch (PDOException $e) {
    error_log('[Museve] 悄悄话管理查询失败: ' . $e->getMessage());
}
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-serif text-museve-night">悄悄话管理</h1>
        <p class="text-sm text-museve-gray mt-1">共 <?= $total ?> 条悄悄话</p>
    </div>
</div>

<!-- 搜索 -->
<div class="mb-6">
    <div class="relative max-w-sm">
        <input type="text" placeholder="搜索悄悄话..." value="<?= htmlspecialchars($search) ?>"
               class="w-full px-4 py-2.5 pl-10 bg-white border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose focus:ring-1 focus:ring-museve-rose/20 transition-colors"
               onkeyup="if(event.key==='Enter')location.href='/admin/whispers?search='+this.value">
        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-museve-gray"></i>
    </div>
    <?php if ($search): ?>
    <a href="/admin/whispers" class="text-xs text-museve-gray hover:text-museve-rose mt-2 inline-block">
        <i class="ph ph-x"></i> 清除搜索
    </a>
    <?php endif; ?>
</div>

<!-- 表格 -->
<div class="bg-white rounded-xl overflow-hidden border border-[#E5E0DB]/50">
    <table class="w-full">
        <thead class="bg-[#F9F7F4] text-left text-xs text-museve-gray uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3">内容预览</th>
                <th class="px-5 py-3">作者</th>
                <th class="px-5 py-3">时间</th>
                <th class="px-5 py-3">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="4" class="px-5 py-12 text-center text-museve-gray">
                <i class="ph ph-chat-circle-dots text-3xl mb-2 block opacity-40"></i>
                暂无悄悄话
            </td></tr>
            <?php else: foreach ($list as $item): ?>
            <tr class="border-t border-[#F5F2F0] hover:bg-[#F9F7F4]/50 transition-colors">
                <td class="px-5 py-3 text-sm max-w-md">
                    <div class="line-clamp-2"><?= htmlspecialchars(mb_substr($item['content'], 0, 100)) ?></div>
                </td>
                <td class="px-5 py-3 text-sm text-museve-gray whitespace-nowrap"><?= htmlspecialchars($item['author_name'] ?? '匿名') ?></td>
                <td class="px-5 py-3 text-sm text-museve-gray whitespace-nowrap"><?= date('m-d H:i', strtotime($item['created_at'])) ?></td>
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <button onclick="openWhisperModal(<?= htmlspecialchars(json_encode($item)) ?>)"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-museve-blue hover:bg-museve-blue/10 transition-colors" title="编辑">
                            <i class="ph ph-pencil-simple text-sm"></i>
                        </button>
                        <button onclick="deleteItem('whispers', <?= $item['id'] ?>)"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-museve-red hover:bg-museve-red/10 transition-colors" title="删除">
                            <i class="ph ph-trash text-sm"></i>
                        </button>
                    </div>
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
    <a href="/admin/whispers?page=<?= $i ?>&search=<?= urlencode($search) ?>"
       class="w-8 h-8 flex items-center justify-center rounded-full text-xs transition-all <?= $i === $page ? 'bg-museve-rose text-white shadow-md' : 'bg-white text-museve-gray hover:bg-museve-haze' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<!-- 编辑悄悄话模态框 -->
<div id="whisperModal" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
        <div class="p-6 border-b border-[#E5E0DB]/50">
            <h2 class="text-lg font-serif font-semibold text-museve-night">编辑悄悄话</h2>
        </div>
        <form id="whisperForm" class="p-6 space-y-4" onsubmit="return false;">
            <input type="hidden" name="id" value="">
            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">内容</label>
                <textarea name="content" rows="5" placeholder="悄悄话内容..."
                          class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors resize-none font-serif"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('whisperModal')"
                        class="px-4 py-2 text-sm text-museve-gray hover:text-museve-night transition-colors">取消</button>
                <button type="button" onclick="saveWhisper()"
                        class="px-5 py-2 bg-museve-rose text-white rounded-lg text-sm hover:bg-museve-rose-deep transition-colors shadow-sm">
                    <i class="ph ph-check mr-1"></i> 保存
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
function openWhisperModal(data) {
    const form = document.getElementById('whisperForm');
    form.reset();
    form.querySelector('[name=id]').value = data.id;
    form.querySelector('[name=content]').value = data.content || '';
    openModal('whisperModal');
}

async function saveWhisper() {
    const form = document.getElementById('whisperForm');
    const id = form.querySelector('[name=id]').value;
    const content = form.querySelector('[name=content]').value.trim();

    if (!content) { showToast('请输入内容', 'error'); return; }

    try {
        const res = await fetch('/admin/api/whispers/' + id, {
            method: 'POST',
            headers: csrfHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify({ _method: 'PUT', content: content })
        });
        const result = await res.json();

        if (result.code === 200) {
            showToast('悄悄话更新成功', 'success');
            closeModal('whisperModal');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(result.message || '操作失败', 'error');
        }
    } catch (e) {
        showToast('网络错误', 'error');
    }
}
</script>
