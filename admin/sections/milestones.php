<?php
// 后台 - 开发历程管理
$db = getDB();
$page = max(1, intval($_GET['page'] ?? 1));
$per = 20;
$offset = ($page - 1) * $per;

$list = [];
$total = 0;
$totalPages = 0;

try {
    $countStmt = $db->query("SELECT COUNT(*) FROM milestones");
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM milestones ORDER BY sort_order ASC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($total / $per);
} catch (PDOException $e) {
    error_log('[Museve] 里程碑管理查询失败: ' . $e->getMessage());
}
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-serif text-museve-night">开发历程管理</h1>
        <p class="text-sm text-museve-gray mt-1">共 <?= $total ?> 个里程碑</p>
    </div>
    <button onclick="openMilestoneModal()" class="bg-museve-rose hover:bg-museve-rose-deep text-white px-4 py-2 rounded-lg text-sm transition-colors">
        <i class="ph ph-plus mr-1"></i> 新增里程碑
    </button>
</div>

<!-- 关于暮想设置 -->
<div class="bg-white rounded-xl p-6 mb-6 border border-[#E5E0DB]/50">
    <h2 class="text-lg font-semibold text-museve-night mb-2">关于暮想</h2>
    <p class="text-xs text-museve-gray mb-3">展示在开发历程页面的品牌介绍文字。</p>
    <?php
    $aboutTextStmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ?");
    $aboutTextStmt->execute(['about_text']);
    $aboutText = $aboutTextStmt->fetchColumn() ?: '';
    ?>
    <textarea id="aboutText" rows="4" placeholder="关于暮想的介绍文字..."
              class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors resize-none font-serif"><?= htmlspecialchars($aboutText) ?></textarea>
    <div class="flex justify-end mt-3">
        <button onclick="saveAboutText()" id="saveAboutBtn"
                class="px-4 py-2 bg-museve-rose text-white rounded-lg text-sm hover:bg-museve-rose-deep transition-colors shadow-sm">
            <i class="ph ph-check mr-1"></i> 保存关于暮想
        </button>
    </div>
</div>

<div class="bg-white rounded-xl overflow-hidden border border-[#E5E0DB]/50">
    <table class="w-full">
        <thead class="bg-[#F9F7F4] text-left text-xs text-museve-gray uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3">排序</th>
                <th class="px-5 py-3">日期</th>
                <th class="px-5 py-3">标题</th>
                <th class="px-5 py-3">描述</th>
                <th class="px-5 py-3">图标</th>
                <th class="px-5 py-3">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="6" class="px-5 py-12 text-center text-museve-gray">暂无里程碑</td></tr>
            <?php else: foreach ($list as $item): ?>
            <tr class="border-t border-[#F5F2F0] hover:bg-[#F9F7F4]/50 transition-colors">
                <td class="px-5 py-3 text-sm text-museve-gray"><?= (int)$item['sort_order'] ?></td>
                <td class="px-5 py-3 text-sm font-medium"><?= htmlspecialchars($item['date']) ?></td>
                <td class="px-5 py-3 text-sm"><?= htmlspecialchars($item['title']) ?></td>
                <td class="px-5 py-3 text-sm text-museve-gray max-w-xs truncate"><?= htmlspecialchars(mb_substr($item['description'] ?? '', 0, 60)) ?></td>
                <td class="px-5 py-3 text-sm font-mono text-museve-blue"><?= htmlspecialchars($item['icon'] ?? 'ph-flower-tulip') ?></td>
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <button onclick="openMilestoneModal(<?= htmlspecialchars(json_encode($item)) ?>)"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-museve-blue hover:bg-museve-blue/10 transition-colors" title="编辑">
                            <i class="ph ph-pencil-simple text-sm"></i>
                        </button>
                        <button onclick="deleteItem('milestones', <?= $item['id'] ?>)"
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

<?php if ($totalPages > 1): ?>
<div class="flex justify-center gap-2 mt-6">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="/admin/milestones?page=<?= $i ?>"
       class="w-8 h-8 flex items-center justify-center rounded-full text-xs transition-all <?= $i === $page ? 'bg-museve-rose text-white shadow-md' : 'bg-white text-museve-gray hover:bg-museve-haze' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<div id="milestoneModal" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-[#E5E0DB]/50">
            <h2 id="milestoneModalTitle" class="text-lg font-serif font-semibold text-museve-night">新增里程碑</h2>
        </div>
        <form id="milestoneForm" class="p-6 space-y-4" onsubmit="return false;">
            <input type="hidden" name="id" value="">
            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">日期 *</label>
                <input type="text" name="date" required placeholder="例如: 2026-06"
                       class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors">
            </div>
            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">标题 *</label>
                <input type="text" name="title" required placeholder="里程碑标题..."
                       class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors">
            </div>
            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">描述</label>
                <textarea name="description" rows="3" placeholder="里程碑描述..."
                          class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors resize-none"></textarea>
            </div>
            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">Phosphor 图标</label>
                <input type="text" name="icon" value="ph-flower-tulip" placeholder="ph-rocket-launch"
                       class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors font-mono">
                <p class="text-[10px] text-museve-gray/50 mt-1">Phosphor 图标名，如 ph-rocket-launch, ph-clock-countdown</p>
            </div>
            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">排序</label>
                <input type="number" name="sort_order" value="0"
                       class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('milestoneModal')"
                        class="px-4 py-2 text-sm text-museve-gray hover:text-museve-night transition-colors">取消</button>
                <button type="button" onclick="saveMilestone()"
                        class="px-5 py-2 bg-museve-rose text-white rounded-lg text-sm hover:bg-museve-rose-deep transition-colors shadow-sm">
                    <i class="ph ph-check mr-1"></i> 保存
                </button>
            </div>
        </form>
    </div>
</div>

<script>
async function saveAboutText() {
    const text = document.getElementById('aboutText').value.trim();
    const res = await fetch('/admin/api/settings', {
        method: 'POST',
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
        body: JSON.stringify({ about_text: text, _method: 'PUT' })
    });
    const result = await res.json();
    if (result.code === 200) {
        showToast('关于暮想已保存', 'success');
    } else {
        showToast(result.message || '保存失败', 'error');
    }
}

function openMilestoneModal(data) {
    const form = document.getElementById('milestoneForm');
    const title = document.getElementById('milestoneModalTitle');
    form.reset();
    form.querySelector('[name=id]').value = '';
    if (data) {
        title.textContent = '编辑里程碑';
        form.querySelector('[name=id]').value = data.id;
        form.querySelector('[name=date]').value = data.date || '';
        form.querySelector('[name=title]').value = data.title || '';
        form.querySelector('[name=description]').value = data.description || '';
        form.querySelector('[name=icon]').value = data.icon || 'ph-flower-tulip';
        form.querySelector('[name=sort_order]').value = data.sort_order || 0;
    } else {
        title.textContent = '新增里程碑';
    }
    openModal('milestoneModal');
}

async function saveMilestone() {
    const form = document.getElementById('milestoneForm');
    const id = form.querySelector('[name=id]').value;
    const data = {
        date: form.querySelector('[name=date]').value.trim(),
        title: form.querySelector('[name=title]').value.trim(),
        description: form.querySelector('[name=description]').value.trim(),
        icon: form.querySelector('[name=icon]').value.trim(),
        sort_order: parseInt(form.querySelector('[name=sort_order]').value) || 0,
    };
    if (!data.title) { showToast('请输入标题', 'error'); return; }
    if (!data.date) { showToast('请输入日期', 'error'); return; }

    const method = 'POST';
    if (id) data._method = 'PUT';
    const url = id ? '/admin/api/milestones/' + id : '/admin/api/milestones';

    try {
        const res = await fetch(url, { method, headers: csrfHeaders({ 'Content-Type': 'application/json' }), body: JSON.stringify(data) });
        const result = await res.json();
        if (result.code === 200 || result.code === 201) {
            showToast(id ? '里程碑更新成功' : '里程碑创建成功', 'success');
            closeModal('milestoneModal');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(result.message || '操作失败', 'error');
        }
    } catch (e) {
        showToast('网络错误', 'error');}
}
</script>
