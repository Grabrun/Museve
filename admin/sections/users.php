<?php
// 后台 - 用户管理
$db = getDB();
$currentUser = ['id' => $_SESSION['admin_id'] ?? 0];

// 默认值
$users = [];

try {
    $stmt = $db->query("SELECT id, account, username, role, avatar, created_at, last_login FROM users ORDER BY id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('[Museve] 用户管理查询失败: ' . $e->getMessage());
}

$roleLabels = ['admin' => '管理员', 'author' => '作者'];
$roleColors = ['admin' => 'bg-[#DDB8B8]/20 text-[#DDB8B8]', 'author' => 'bg-[#A8C5DA]/20 text-[#A8C5DA]'];
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-serif text-[#3E3640]">用户管理</h1>
    <button onclick="openModal('addUserModal')" class="bg-[#DDB8B8] hover:bg-[#B28B8B] text-white px-4 py-2 rounded-lg text-sm transition-colors">+ 新增用户</button>
</div>

<div class="bg-white rounded-xl overflow-hidden">
    <table class="w-full">
        <thead class="bg-[#F9F7F4] text-left text-sm text-[#8E827F]">
            <tr><th class="px-5 py-3">头像</th><th class="px-5 py-3">昵称</th><th class="px-5 py-3">账号</th><th class="px-5 py-3">角色</th><th class="px-5 py-3">注册时间</th><th class="px-5 py-3">最后登录</th><th class="px-5 py-3">操作</th></tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
            <tr><td colspan="7" class="px-5 py-10 text-center text-[#8E827F]">暂无用户</td></tr>
            <?php else: foreach ($users as $user): ?>
            <tr class="border-t border-[#F5F2F0] hover:bg-[#F9F7F4]/50">
                <td class="px-5 py-3">
                    <img src="<?= htmlspecialchars($user['avatar'] ?: '/resources/images/default-avatar.png') ?>" class="w-10 h-10 rounded-full object-cover">
                </td>
                <td class="px-5 py-3 text-sm"><?= htmlspecialchars($user['username']) ?></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($user['account']) ?></td>
                <td class="px-5 py-3"><span class="px-2 py-1 rounded-full text-xs <?= $roleColors[$user['role']] ?? '' ?>"><?= $roleLabels[$user['role']] ?? $user['role'] ?></span></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($user['created_at']) ?></td>
                <td class="px-5 py-3 text-sm text-[#8E827F]"><?= htmlspecialchars($user['last_login'] ?? '从未') ?></td>
                <td class="px-5 py-3">
                    <div class="flex items-center gap-2">
                        <button onclick="openEditUserModal(<?= htmlspecialchars(json_encode($user)) ?>)"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-museve-blue hover:bg-museve-blue/10 transition-colors" title="编辑">
                            <i class="ph ph-pencil-simple text-sm"></i>
                        </button>
                        <?php if ($user['id'] !== $currentUser['id']): ?>
                        <button onclick="deleteItem('users', <?= $user['id'] ?>)"
                                class="w-8 h-8 rounded-full flex items-center justify-center text-museve-red hover:bg-museve-red/10 transition-colors" title="删除">
                            <i class="ph ph-trash text-sm"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- 新增用户模态框 -->
<div id="addUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4 shadow-2xl">
        <h2 class="text-xl font-serif text-[#3E3640] mb-6">新增用户</h2>
        <form id="addUserForm" class="space-y-4">
            <div>
                <label class="text-sm text-[#8E827F]">账号</label>
                <input type="text" name="account" required class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">昵称</label>
                <input type="text" name="username" required class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">密码</label>
                <input type="password" name="password" required class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">角色</label>
                <select name="role" class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]">
                    <option value="author">作者</option>
                    <option value="admin">管理员</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeModal('addUserModal')" class="px-4 py-2 text-sm text-[#8E827F] hover:text-[#3E3640]">取消</button>
                <button type="submit" class="px-5 py-2 bg-[#DDB8B8] text-white rounded-lg text-sm hover:bg-[#B28B8B] transition-colors">创建</button>
            </div>
        </form>
    </div>
</div>

<!-- 编辑用户模态框 -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl p-8 w-full max-w-md mx-4 shadow-2xl">
        <h2 class="text-xl font-serif text-[#3E3640] mb-6">编辑用户</h2>
        <form id="editUserForm" class="space-y-4">
            <input type="hidden" name="id" value="">
            <div>
                <label class="text-sm text-[#8E827F]">头像</label>
                <div class="mt-2 flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-[#F9F7F4] border border-[#E5E0DB] overflow-hidden">
                        <img id="editUserAvatar" src="/resources/images/default-avatar.png" class="w-full h-full object-cover">
                    </div>
                    <input type="file" id="editAvatarInput" accept="image/*" class="text-sm text-[#8E827F]">
                </div>
                <input type="hidden" name="avatar" id="editAvatarUrl" value="">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">账号</label>
                <input type="text" name="account" class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">昵称</label>
                <input type="text" name="username" class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">角色</label>
                <select name="role" class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]">
                    <option value="author">作者</option>
                    <option value="admin">管理员</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">新密码（留空不修改）</label>
                <input type="password" name="password" class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8]">
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeModal('editUserModal')" class="px-4 py-2 text-sm text-[#8E827F] hover:text-[#3E3640]">取消</button>
                <button type="submit" class="px-5 py-2 bg-[#DDB8B8] text-white rounded-lg text-sm hover:bg-[#B28B8B] transition-colors">保存</button>
            </div>
        </form>
    </div>
</div>

<script>
// 新增用户
function openEditUserModal(data) {
    const form = document.getElementById('editUserForm');
    form.querySelector('[name=id]').value = data.id;
    form.querySelector('[name=account]').value = data.account || '';
    form.querySelector('[name=username]').value = data.username || '';
    form.querySelector('[name=role]').value = data.role || 'author';
    form.querySelector('[name=password]').value = '';
    form.querySelector('[name=avatar]').value = data.avatar || '';
    document.getElementById('editUserAvatar').src = data.avatar || '/resources/images/default-avatar.png';
    openModal('editUserModal');
}

document.getElementById('editAvatarInput')?.addEventListener('change', async (e) => {
    const file = e.target.files[0];
    if (!file) return;
    if (!file.type.startsWith('image/')) { showToast('请选择图片文件', 'error'); return; }
    const formData = new FormData();
    formData.append('file', file);
    try {
        const res = await fetch('/admin/api/upload', { method: 'POST', body: formData, headers: { 'X-CSRF-Token': getCsrfToken() } });
        const result = await res.json();
        if (result.code === 200) {
            document.getElementById('editUserAvatar').src = result.data.url;
            document.getElementById('editAvatarUrl').value = result.data.url;
            showToast('头像上传成功', 'success');
        } else {
            showToast(result.message || '上传失败', 'error');
        }
    } catch(e) { showToast('上传失败', 'error'); }
});

document.getElementById('editUserForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const data = { id: form.querySelector('[name=id]').value, _method: 'PUT' };
    ['account', 'username', 'role', 'password', 'avatar'].forEach(f => {
        const val = form.querySelector('[name="' + f + '"]')?.value;
        if (val) data[f] = val;
    });
    const res = await fetch('/admin/api/users', {
        method: 'POST',
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
        body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.code === 200) {
        showToast('用户更新成功', 'success');
        closeModal('editUserModal');
        setTimeout(() => location.reload(), 500);
    } else {
        showToast(result.message || '更新失败', 'error');
    }
});

document.getElementById('addUserForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    const data = Object.fromEntries(form);
    const res = await fetch('/admin/api/users', {
        method: 'POST',
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
        body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.code === 201) {
        showToast('用户创建成功', 'success');
        setTimeout(() => location.reload(), 500);
    } else {
        showToast(result.message || '创建失败', 'error');
    }
});
</script>
