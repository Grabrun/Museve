<?php
// 后台 - 网站设置
$db = getDB();

// 默认值
$settings = [];

try {
    $stmt = $db->query("SELECT `key`, `value` FROM settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }
} catch (PDOException $e) {
    error_log('[Museve] 网站设置查询失败: ' . $e->getMessage());
}
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-serif text-[#3E3640]">网站设置</h1>
    <button onclick="saveSettings()" id="saveBtn" class="bg-[#DDB8B8] hover:bg-[#B28B8B] text-white px-5 py-2 rounded-lg text-sm transition-colors disabled:opacity-50" disabled>保存设置</button>
</div>

<div id="unsavedBanner" class="hidden mb-4 bg-[#E0A96D]/10 border border-[#E0A96D]/30 rounded-lg px-4 py-3 text-sm text-[#E0A96D] flex items-center gap-2">
    <span>⚠️</span> 有未保存的更改
</div>

<form id="settingsForm" class="space-y-8">
    <!-- 基本信息 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#3E3640] mb-4">基本信息</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-[#8E827F]">网站标题</label>
                <input type="text" name="site_title" value="<?= htmlspecialchars($settings['site_title'] ?? '暮想') ?>"
                       class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">副标题</label>
                <input type="text" name="site_subtitle" value="<?= htmlspecialchars($settings['site_subtitle'] ?? '在薄暮时分，温柔地想起。') ?>"
                       class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
            </div>
        </div>
    </div>

    <!-- 品牌资源 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#3E3640] mb-4">品牌资源</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="text-sm text-[#8E827F]">网站 Logo</label>
                <div class="mt-2 flex items-center gap-4">
                    <div class="w-20 h-20 rounded-lg bg-[#F9F7F4] border border-[#E5E0DB] flex items-center justify-center overflow-hidden">
                        <img id="logoPreview" src="<?= htmlspecialchars($settings['site_logo'] ?? '') ?>" class="max-w-full max-h-full" style="<?= empty($settings['site_logo']) ? 'display:none' : '' ?>">
                        <span id="logoPlaceholder" class="text-xs text-[#8E827F]" style="<?= !empty($settings['site_logo']) ? 'display:none' : '' ?>">Logo</span>
                    </div>
                    <div class="flex-1">
                        <input type="file" accept="image/*" onchange="previewImage(this, 'logoPreview', 'logoPlaceholder')" class="text-sm text-[#8E827F]">
                        <input type="text" name="site_logo" value="<?= htmlspecialchars($settings['site_logo'] ?? '') ?>" placeholder="Logo URL 或上传"
                               class="w-full mt-1.5 px-3 py-1.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded text-xs focus:outline-none focus:border-[#DDB8B8] setting-input">
                        <p class="text-xs text-[#8E827F] mt-1">建议尺寸 200×200，可填URL或上传</p>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">默认头像</label>
                <div class="mt-2 flex items-center gap-4">
                    <div class="w-20 h-20 rounded-full bg-[#F9F7F4] border border-[#E5E0DB] flex items-center justify-center overflow-hidden">
                        <img id="avatarPreview" src="<?= htmlspecialchars($settings['site_avatar'] ?? '/resources/images/default-avatar.png') ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <input type="file" accept="image/*" onchange="previewImage(this, 'avatarPreview')" class="text-sm text-[#8E827F]">
                        <input type="text" name="site_avatar" value="<?= htmlspecialchars($settings['site_avatar'] ?? '') ?>" placeholder="默认头像 URL 或上传"
                               class="w-full mt-1.5 px-3 py-1.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded text-xs focus:outline-none focus:border-[#DDB8B8] setting-input">
                        <p class="text-xs text-[#8E827F] mt-1">建议正方形，可填URL或上传</p>
                    </div>
                </div>
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">首页头像（引语上方）</label>
                <div class="mt-2 flex items-center gap-4">
                    <div class="w-20 h-20 rounded-full bg-[#F9F7F4] border border-[#E5E0DB] flex items-center justify-center overflow-hidden">
                        <img id="homeAvatarPreview" src="<?= htmlspecialchars($settings['site_avatar'] ?? '/resources/images/default-avatar.png') ?>" class="w-full h-full object-cover">
                    </div>
                    <div>
                        <input type="file" accept="image/*" onchange="previewImage(this, 'homeAvatarPreview')" class="text-sm text-[#8E827F]">
                        <input type="text" name="home_avatar" value="<?= htmlspecialchars($settings['home_avatar'] ?? '') ?>" placeholder="留空使用默认头像"
                               class="w-full mt-1 px-3 py-1.5 bg-[#F9F7F4] border border-[#E5E0DB] rounded text-xs focus:outline-none focus:border-[#DDB8B8] setting-input">
                        <p class="text-xs text-[#8E827F] mt-1">建议正方形，可填URL或上传</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 备案信息 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#3E3640] mb-4">备案信息</h2>
        <div>
            <label class="text-sm text-[#8E827F]">网站备案号</label>
            <input type="text" name="icp_number" value="<?= htmlspecialchars($settings['icp_number'] ?? '') ?>"
                   placeholder="粤ICP备XXXXXXXX号"
                   class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
        </div>
        <div class="mt-4">
            <label class="text-sm text-[#8E827F]">公安联网备案号</label>
            <input type="text" name="police_icp" value="<?= htmlspecialchars($settings['police_icp'] ?? '') ?>"
                   placeholder="XX公网安备XXXXXXXX号"
                   class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
        </div>
    </div>

    <!-- SEO 设置 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#3E3640] mb-4">SEO 设置</h2>
        <div class="space-y-4">
            <div>
                <label class="text-sm text-[#8E827F]">站点描述 (meta description)</label>
                <textarea name="site_description" rows="2"
                          placeholder="网站描述，用于搜索引擎展示"
                          class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input resize-none"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">关键词 (meta keywords)</label>
                <input type="text" name="site_keywords" value="<?= htmlspecialchars($settings['site_keywords'] ?? '') ?>"
                       placeholder="暮想, Museve, 回忆, 悄悄话, 文章"
                       class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
            </div>
        </div>
    </div>

    <!-- 上传设置 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#3E3640] mb-4">上传设置</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-[#8E827F]">最大上传大小 (MB)</label>
                <input type="number" name="upload_max_size" value="<?= htmlspecialchars($settings['upload_max_size'] ?? '5') ?>"
                       min="1" max="100"
                       class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">允许的文件类型</label>
                <input type="text" name="upload_allowed_types" value="<?= htmlspecialchars($settings['upload_allowed_types'] ?? 'image/jpeg,image/png,image/gif,image/webp') ?>"
                       placeholder="image/jpeg,image/png"
                       class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
            </div>
        </div>
    </div>

    <!-- 高级设置 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#3E3640] mb-4">高级设置</h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-[#8E827F]">CDN 前缀</label>
                    <input type="text" name="cdn_prefix" value="<?= htmlspecialchars($settings['cdn_prefix'] ?? '') ?>"
                           placeholder="https://cdn.example.com"
                           class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
                </div>
                <div>
                    <label class="text-sm text-[#8E827F]">登录凭据有效期（分钟）</label>
                    <input type="number" name="login_token_expiry" value="<?= htmlspecialchars($settings['login_token_expiry'] ?? '30') ?>"
                           min="5" max="1440"
                           class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
                </div>
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">维护模式</label>
                <div class="mt-2 flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="maintenance_mode" value="1" <?= ($settings['maintenance_mode'] ?? '0') === '1' ? 'checked' : '' ?> class="text-[#DDB8B8]">
                        <span class="text-sm text-[#D18B8B]">开启</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="maintenance_mode" value="0" <?= ($settings['maintenance_mode'] ?? '0') !== '1' ? 'checked' : '' ?> class="text-[#DDB8B8]">
                        <span class="text-sm text-[#87A878]">关闭</span>
                    </label>
                    <span class="text-xs text-[#8E827F] ml-2">开启后前台将显示维护提示页面</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚设置 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#3E3640] mb-4">页脚设置</h2>
        <div>
            <label class="text-sm text-[#8E827F]">自定义页脚 HTML</label>
            <textarea name="custom_footer" rows="3"
                      placeholder="页脚额外内容（支持 HTML）"
                      class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input font-mono resize-none"><?= htmlspecialchars($settings['custom_footer'] ?? '') ?></textarea>
        </div>
        <div class="mt-4">
            <label class="text-sm text-[#8E827F]">版权信息</label>
            <input type="text" name="copyright" value="<?= htmlspecialchars($settings['copyright'] ?? '') ?>"
                   placeholder="© 2026 暮想 Museve"
                   class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
        </div>
    </div>

    <!-- 引语设置 -->
    <div class="bg-white rounded-xl p-6">
        <h2 class="text-lg font-semibold text-[#3E3640] mb-2">引语设置</h2>
        <p class="text-xs text-[#8E827F] mb-4">首页打字机效果依次展示的引语，留空则使用默认值。</p>
        <div class="space-y-4">
            <div>
                <label class="text-sm text-[#8E827F]">引语 1</label>
                <input type="text" name="quote_1" value="<?= htmlspecialchars($settings['quote_1'] ?? '') ?>"
                       placeholder="在薄暮时分，温柔地想起。"
                       class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">引语 2</label>
                <input type="text" name="quote_2" value="<?= htmlspecialchars($settings['quote_2'] ?? '') ?>"
                       placeholder="时光如水，回忆如花。"
                       class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
            </div>
            <div>
                <label class="text-sm text-[#8E827F]">引语 3</label>
                <input type="text" name="quote_3" value="<?= htmlspecialchars($settings['quote_3'] ?? '') ?>"
                       placeholder="你的回忆，值得被温柔珍藏。"
                       class="w-full mt-1 px-3 py-2 bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg text-sm focus:outline-none focus:border-[#DDB8B8] setting-input">
            </div>
        </div>
    </div>
</form>

<script>
// 监听变化
document.querySelectorAll('.setting-input').forEach(input => {
    input.addEventListener('input', () => {
        document.getElementById('saveBtn').disabled = false;
        document.getElementById('unsavedBanner').classList.remove('hidden');
    });
});

function previewImage(input, previewId, placeholderId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(previewId).style.display = '';
            if (placeholderId) document.getElementById(placeholderId).style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
        document.getElementById('saveBtn').disabled = false;
        document.getElementById('unsavedBanner').classList.remove('hidden');
    }
}

async function saveSettings() {
    const form = document.getElementById('settingsForm');
    const data = {};
    form.querySelectorAll('.setting-input').forEach(input => {
        data[input.name] = input.value;
    });

    const res = await fetch('/admin/api/settings', {
        method: 'POST',
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
        body: JSON.stringify({ ...data, _method: 'PUT' })
    });

    const result = await res.json();
    if (result.code === 200) {
        showToast('设置保存成功', 'success');
        document.getElementById('saveBtn').disabled = true;
        document.getElementById('unsavedBanner').classList.add('hidden');
    } else {
        showToast(result.message || '保存失败', 'error');
    }
}
</script>
