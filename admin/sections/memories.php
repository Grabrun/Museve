<?php
// 后台 - 回忆管理
$db = getDB();
$page = max(1, intval($_GET['page'] ?? 1));
$per = 10;
$offset = ($page - 1) * $per;
$search = trim($_GET['search'] ?? '');

$list = [];
$total = 0;
$totalPages = 0;

try {
    $where = '';
    $params = [];
    // 当前用户信息（已在 admin/index.php 中鉴权并设置 Session）
    $authorId = (int)($_SESSION['admin_id'] ?? 0);
    $authorRole = (string)($_SESSION['admin_role'] ?? '');

    if ($search) {
        $where = "WHERE m.title LIKE ?";
        $params[] = "%$search%";
    }

    // 非管理员只能看到自己创建的内容
    if ($authorRole !== 'admin') {
        $where .= ($where ? ' AND' : 'WHERE') . ' m.author_id = ?';
        $params[] = $authorId;
    }

    $countStmt = $db->prepare("SELECT COUNT(*) FROM memories m $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $db->prepare("SELECT m.*, u.username as author_name FROM memories m LEFT JOIN users u ON m.author_id = u.id $where ORDER BY m.event_time DESC LIMIT " . (int)$per . " OFFSET " . (int)$offset);
    foreach ($params as $i => $p) $stmt->bindValue($i + 1, $p);
    $stmt->execute();
    $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($total / $per);
} catch (PDOException $e) {
    error_log('[Museve] 回忆管理查询失败: ' . $e->getMessage());
}
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-serif text-museve-night">回忆管理</h1>
        <p class="text-sm text-museve-gray mt-1">共 <?= $total ?> 条回忆</p>
    </div>
    <button onclick="openMemoryModal()" class="bg-museve-rose hover:bg-museve-rose-deep text-white px-4 py-2 rounded-lg text-sm transition-colors">
        <i class="ph ph-plus mr-1"></i> 新增回忆
    </button>
</div>

<!-- 搜索 -->
<div class="mb-6">
    <div class="relative max-w-sm">
        <input type="text" placeholder="搜索回忆..." value="<?= htmlspecialchars($search) ?>"
               class="w-full px-4 py-2.5 pl-10 bg-white border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose focus:ring-1 focus:ring-museve-rose/20 transition-colors"
               onkeyup="if(event.key==='Enter')location.href='/admin/memories?search='+this.value">
        <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-museve-gray"></i>
    </div>
    <?php if ($search): ?>
    <a href="/admin/memories" class="text-xs text-museve-gray hover:text-museve-rose mt-2 inline-block">
        <i class="ph ph-x"></i> 清除搜索
    </a>
    <?php endif; ?>
</div>

<!-- 表格 -->
<div class="bg-white rounded-xl overflow-hidden border border-[#E5E0DB]/50">
    <table class="w-full">
        <thead class="bg-[#F9F7F4] text-left text-xs text-museve-gray uppercase tracking-wider">
            <tr>
                <th class="px-5 py-3">缩略图</th>
                <th class="px-5 py-3">标题</th>
                <th class="px-5 py-3">事件时间</th>
                <th class="px-5 py-3">创建者</th>
                <th class="px-5 py-3">操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
            <tr><td colspan="5" class="px-5 py-12 text-center text-museve-gray">
                <i class="ph ph-clock-counter-clockwise text-3xl mb-2 block opacity-40"></i>
                暂无回忆
            </td></tr>
            <?php else: foreach ($list as $item): ?>
            <tr class="border-t border-[#F5F2F0] hover:bg-[#F9F7F4]/50 transition-colors">
                <td class="px-5 py-3">
                    <?php if ($item['image']): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" class="w-12 h-12 object-cover rounded-lg">
                    <?php else: ?>
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-museve-rose to-museve-blue flex items-center justify-center">
                        <i class="ph ph-image text-white/60"></i>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-sm font-medium"><?= htmlspecialchars($item['title']) ?></td>
                <td class="px-5 py-3 text-sm text-museve-gray">
                    <span class="inline-flex items-center gap-1"><i class="ph ph-calendar text-xs"></i> <?= htmlspecialchars($item['event_time']) ?></span>
                </td>
                <td class="px-5 py-3 text-sm text-museve-gray"><?= htmlspecialchars($item['author_name'] ?? '未知') ?></td>
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <button onclick="openMemoryModal(<?= htmlspecialchars(json_encode($item)) ?>)"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-museve-blue hover:bg-museve-blue/10 transition-colors" title="编辑">
                            <i class="ph ph-pencil-simple text-sm"></i>
                        </button>
                        <button onclick="deleteItem('memories', <?= $item['id'] ?>)"
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
    <a href="/admin/memories?page=<?= $i ?>&search=<?= urlencode($search) ?>"
       class="w-8 h-8 flex items-center justify-center rounded-full text-xs transition-all <?= $i === $page ? 'bg-museve-rose text-white shadow-md' : 'bg-white text-museve-gray hover:bg-museve-haze' ?>">
        <?= $i ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<!-- 新增/编辑回忆模态框 -->
<div id="memoryModal" class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-[#E5E0DB]/50">
            <h2 id="memoryModalTitle" class="text-lg font-serif font-semibold text-museve-night">新增回忆</h2>
        </div>
        <form id="memoryForm" class="p-6 space-y-4" onsubmit="return false;">
            <input type="hidden" name="id" value="">

            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">标题 *</label>
                <input type="text" name="title" required placeholder="回忆标题..."
                       class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors">
            </div>

            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">事件时间 *</label>
                <input type="datetime-local" name="event_time" required
                       class="w-full px-3 py-2.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors">
            </div>

            <div>
                <label class="text-xs font-medium text-museve-gray mb-1.5 block">封面图片</label>
                <div id="memoryCoverDropzone" class="relative border-2 border-dashed border-[#E5E0DB] rounded-xl overflow-hidden transition-colors hover:border-museve-rose/50 cursor-pointer group">
                    <img id="memoryCoverPreview" src="" class="w-full h-32 object-cover hidden">
                    <div id="memoryCoverPlaceholder" class="h-32 flex flex-col items-center justify-center text-museve-gray/60">
                        <i class="ph ph-image text-2xl mb-1 group-hover:text-museve-rose transition-colors"></i>
                        <span class="text-xs">拖拽或点击上传</span>
                    </div>
                    <input type="file" id="memoryCoverInput" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                </div>
                <input type="text" name="image" id="memoryImageUrl" value="" placeholder="图片URL或点击上方上传..."
                       class="w-full mt-2 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-museve-rose transition-colors">
                <p class="text-[10px] text-museve-gray/50 mt-2">支持 JPG/PNG/WebP，建议 16:9</p>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeModal('memoryModal')"
                        class="px-4 py-2 text-sm text-museve-gray hover:text-museve-night transition-colors">取消</button>
                <button type="button" onclick="saveMemory()"
                        class="px-5 py-2 bg-museve-rose text-white rounded-lg text-sm hover:bg-museve-rose-deep transition-colors shadow-sm">
                    <i class="ph ph-check mr-1"></i> 保存
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// 图片上传处理
const memDropzone = document.getElementById('memoryCoverDropzone');
const memCoverInput = document.getElementById('memoryCoverInput');
const memCoverPreview = document.getElementById('memoryCoverPreview');
const memCoverPlaceholder = document.getElementById('memoryCoverPlaceholder');
const memImageUrl = document.getElementById('memoryImageUrl');

if (memDropzone) {
    ['dragenter', 'dragover'].forEach(e => {
        memDropzone.addEventListener(e, (ev) => { ev.preventDefault(); memDropzone.classList.add('border-museve-rose'); });
    });
    ['dragleave', 'drop'].forEach(e => {
        memDropzone.addEventListener(e, (ev) => { ev.preventDefault(); memDropzone.classList.remove('border-museve-rose'); });
    });
    memDropzone.addEventListener('drop', (e) => {
        const file = e.dataTransfer.files[0];
        if (file) uploadMemoryCover(file);
    });
    memCoverInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) uploadMemoryCover(file);
    });
}

async function uploadMemoryCover(file) {
    if (!file.type.startsWith('image/')) { showToast('请选择图片文件', 'error'); return; }
    if (file.size > 5 * 1024 * 1024) { showToast('图片不能超过 5MB', 'error'); return; }
    const formData = new FormData();
    formData.append('file', file);
    try {
        const res = await fetch('/admin/api/upload', { method: 'POST', body: formData, headers: { 'X-CSRF-Token': getCsrfToken() } });
        const data = await res.json();
        if (data.code === 200) {
            memImageUrl.value = data.data.url;
            memCoverPreview.src = data.data.url;
            memCoverPreview.classList.remove('hidden');
            memCoverPlaceholder.classList.add('hidden');
            showToast('图片上传成功', 'success');
        } else {
            showToast(data.message || '上传失败', 'error');
        }
    } catch (e) {
        showToast('上传失败', 'error');
    }
}

function openMemoryModal(data) {
    const form = document.getElementById('memoryForm');
    const title = document.getElementById('memoryModalTitle');

    form.reset();
    form.querySelector('[name=id]').value = '';
    memImageUrl.value = '';
    memCoverPreview.src = '';
    memCoverPreview.classList.add('hidden');
    memCoverPlaceholder.classList.remove('hidden');

    if (data) {
        title.textContent = '编辑回忆';
        form.querySelector('[name=id]').value = data.id;
        form.querySelector('[name=title]').value = data.title || '';
        form.querySelector('[name=event_time]').value = (data.event_time || '').replace(' ', 'T').substring(0, 16);
        if (data.image) {
            memImageUrl.value = data.image;
            memCoverPreview.src = data.image;
            memCoverPreview.classList.remove('hidden');
            memCoverPlaceholder.classList.add('hidden');
        }
    } else {
        title.textContent = '新增回忆';
        form.querySelector('[name=event_time]').value = new Date().toLocaleString('sv-SE').slice(0, 16);
    }

    openModal('memoryModal');
}

async function saveMemory() {
    const form = document.getElementById('memoryForm');
    const id = form.querySelector('[name=id]').value;
    const data = {
        title: form.querySelector('[name=title]').value.trim(),
        event_time: form.querySelector('[name=event_time]').value,
        image: memImageUrl.value.trim(),
    };

    if (!data.title) { showToast('请输入标题', 'error'); return; }
    if (!data.event_time) { showToast('请选择事件时间', 'error'); return; }

    const method = 'POST';
    if (id) data._method = 'PUT';
    const url = id ? '/admin/api/memories/' + id : '/admin/api/memories';

    try {
        const res = await fetch(url, {
            method,
            headers: csrfHeaders({ 'Content-Type': 'application/json' }),
            body: JSON.stringify(data)
        });
        const result = await res.json();

        if (result.code === 200 || result.code === 201) {
            showToast(id ? '回忆更新成功' : '回忆创建成功', 'success');
            closeModal('memoryModal');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast(result.message || '操作失败', 'error');
        }
    } catch (e) {
        showToast('网络错误', 'error');
    }
}
</script>

