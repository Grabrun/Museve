<?php
// 后台 - 文章编辑
$db = getDB();
$id = intval($_GET['id'] ?? 0);
$article = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$id]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$article) { http_response_code(404); echo '文章不存在'; exit; }
}

$isEdit = !empty($article);
$pageTitle = $isEdit ? '编辑文章' : '新增文章';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-serif text-museve-night"><?= $pageTitle ?></h1>
        <?php if ($isEdit): ?>
        <p class="text-xs text-museve-gray mt-1">最后修改: <?= htmlspecialchars($article['updated_at'] ?? '') ?></p>
        <?php endif; ?>
    </div>
    <a href="/admin/articles" class="flex items-center gap-1 text-sm text-museve-gray hover:text-museve-night transition-colors">
        <i class="ph ph-arrow-left"></i> 返回列表
    </a>
</div>

<form id="articleForm" class="space-y-6 pb-24">
    <input type="hidden" name="id" value="<?= $id ?>">

    <!-- 标题 -->
    <div>
        <input type="text" name="title" value="<?= htmlspecialchars($article['title'] ?? '') ?>" placeholder="写下标题..."
               class="w-full text-2xl font-serif bg-transparent border-b-2 border-[#E5E0DB] focus:border-museve-rose outline-none pb-2 transition-colors placeholder:text-museve-gray/40">
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- 编辑器 (3/4) -->
        <div class="lg:col-span-3">
            <textarea id="article-editor" name="content" class="w-full min-h-[500px]"><?= htmlspecialchars($article['content'] ?? '') ?></textarea>
        </div>

        <!-- 侧边面板 (1/4) -->
        <div class="space-y-5">
            <!-- 状态选择 -->
            <div class="bg-white rounded-xl p-5 border border-[#E5E0DB]/50">
                <h3 class="text-xs font-medium text-museve-gray uppercase tracking-wider mb-3">发布状态</h3>
                <div class="space-y-2">
                    <?php
                    $statuses = [
                        'draft' => ['label' => '草稿', 'color' => 'museve-gray', 'icon' => 'ph-pencil-simple'],
                        'published' => ['label' => '发布', 'color' => 'museve-green', 'icon' => 'ph-check-circle'],
                        'pending' => ['label' => '待审核', 'color' => 'museve-orange', 'icon' => 'ph-clock'],
                    ];
                    $currentStatus = $article['status'] ?? 'draft';
                    foreach ($statuses as $val => $s): ?>
                    <label class="flex items-center gap-3 p-2.5 rounded-lg cursor-pointer transition-colors <?= $currentStatus === $val ? 'bg-'.$s['color'].'/10 border border-'.$s['color'].'/30' : 'hover:bg-museve-haze border border-transparent' ?>">
                        <input type="radio" name="status" value="<?= $val ?>" <?= $currentStatus === $val ? 'checked' : '' ?>
                               class="sr-only">
                        <i class="<?= $s['icon'] ?> text-lg <?= $currentStatus === $val ? 'text-'.$s['color'] : 'text-museve-gray' ?>"></i>
                        <span class="text-sm <?= $currentStatus === $val ? 'font-medium text-'.$s['color'] : 'text-museve-gray' ?>"><?= $s['label'] ?></span>
                        <?php if ($currentStatus === $val): ?>
                        <i class="ph ph-check text-sm ml-auto text-<?= $s['color'] ?>"></i>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 封面上传 -->
            <div class="bg-white rounded-xl p-5 border border-[#E5E0DB]/50">
                <h3 class="text-xs font-medium text-museve-gray uppercase tracking-wider mb-3">封面图片</h3>
                <div id="coverDropzone" class="relative border-2 border-dashed border-[#E5E0DB] rounded-xl overflow-hidden transition-colors hover:border-museve-rose/50 cursor-pointer group">
                    <?php if (!empty($article['cover'])): ?>
                    <img id="coverPreview" src="<?= htmlspecialchars($article['cover']) ?>" class="w-full h-32 object-cover">
                    <?php else: ?>
                    <div id="coverPlaceholder" class="h-32 flex flex-col items-center justify-center text-museve-gray/60">
                        <i class="ph ph-image text-2xl mb-1 group-hover:text-museve-rose transition-colors"></i>
                        <span class="text-xs">拖拽或点击上传</span>
                    </div>
                    <img id="coverPreview" src="" class="w-full h-32 object-cover hidden">
                    <?php endif; ?>
                    <input type="file" id="coverInput" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                </div>
                <input type="hidden" name="cover" id="coverUrl" value="<?= htmlspecialchars($article['cover'] ?? '') ?>">
                <p class="text-[10px] text-museve-gray/50 mt-2">支持 JPG/PNG/WebP，建议 16:9</p>
            </div>
        </div>
    </div>

    <!-- 底部固定保存条 -->
    <div class="fixed bottom-0 left-0 right-0 lg:left-[72px] bg-white/90 backdrop-blur-xl border-t border-[#E5E0DB]/50 px-6 py-3 flex items-center justify-between z-20">
        <div class="flex items-center gap-2 text-xs text-museve-gray">
            <i class="ph ph-info"></i>
            <span>Ctrl+S 保存草稿</span>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="saveArticle('draft')"
                    class="px-5 py-2 bg-museve-haze text-museve-night rounded-lg text-sm hover:bg-[#E5E0DB] transition-colors">
                <i class="ph ph-floppy-disk mr-1"></i> 保存草稿
            </button>
            <button type="button" onclick="saveArticle('published')"
                    class="px-5 py-2 bg-museve-rose text-white rounded-lg text-sm hover:bg-museve-rose-deep transition-colors shadow-sm">
                <i class="ph ph-paper-plane-tilt mr-1"></i> 发布文章
            </button>
        </div>
    </div>
</form>

<!-- DOMPurify -->
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.0.6/dist/purify.min.js"></script>
<!-- TinyMCE -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
<script src="/resources/js/admin.js"></script>

<script>
// TinyMCE 初始化
tinymce.init({
    selector: '#article-editor',
    height: 500,
    skin: 'oxide-dark',
    content_css: 'default',
    content_style: 'body { font-family: "Noto Serif SC", "Source Han Serif SC", serif; color: #3E3640; line-height: 1.8; font-size: 1.125rem; padding: 1rem 2rem; } p { margin-bottom: 1em; } blockquote { border-left: 3px solid #DDB8B8; padding-left: 1rem; margin: 1em 0; background: #F9F7F4; border-radius: 0 8px 8px 0; padding: 0.75rem 1rem; } pre { background: #F5F2F0; border-radius: 8px; padding: 1rem; overflow-x: auto; } code { background: #F5F2F0; padding: 2px 6px; border-radius: 4px; font-size: 0.9em; }',
    plugins: 'lists link image table code codesample searchreplace visualblocks fullscreen',
    toolbar: 'undo redo | blocks | bold italic strikethrough | alignleft aligncenter alignright | bullist numlist | link image codesample | searchreplace code fullscreen',
    branding: false,
    menubar: false,
    convert_urls: false,
    images_upload_url: '/admin/api/upload',
    automatic_uploads: true,
    file_picker_types: 'image',
});

// 封面拖拽上传
const dropzone = document.getElementById('coverDropzone');
const coverInput = document.getElementById('coverInput');
const coverPreview = document.getElementById('coverPreview');
const coverUrl = document.getElementById('coverUrl');
const coverPlaceholder = document.getElementById('coverPlaceholder');

if (dropzone) {
    ['dragenter', 'dragover'].forEach(e => {
        dropzone.addEventListener(e, (ev) => { ev.preventDefault(); dropzone.classList.add('border-museve-rose'); });
    });
    ['dragleave', 'drop'].forEach(e => {
        dropzone.addEventListener(e, (ev) => { ev.preventDefault(); dropzone.classList.remove('border-museve-rose'); });
    });
    dropzone.addEventListener('drop', (e) => {
        const file = e.dataTransfer.files[0];
        if (file) uploadCover(file);
    });
    coverInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) uploadCover(file);
    });
}

async function uploadCover(file) {
    if (!file.type.startsWith('image/')) { showToast('请选择图片文件', 'error'); return; }
    if (file.size > 5 * 1024 * 1024) { showToast('图片不能超过 5MB', 'error'); return; }

    const formData = new FormData();
    formData.append('file', file);

    try {
        const res = await fetch('/admin/api/upload', { method: 'POST', body: formData, headers: { 'X-CSRF-Token': getCsrfToken() } });
        const data = await res.json();
        if (data.code === 200) {
            coverUrl.value = data.data.url;
            coverPreview.src = data.data.url;
            coverPreview.classList.remove('hidden');
            if (coverPlaceholder) coverPlaceholder.classList.add('hidden');
            showToast('封面上传成功', 'success');
        } else {
            showToast(data.message || '上传失败', 'error');
        }
    } catch (e) {
        showToast('上传失败', 'error');
    }
}

// 保存文章 (带 DOMPurify 过滤)
async function saveArticle(status) {
    const form = document.getElementById('articleForm');
    let content = typeof tinymce !== 'undefined' ? tinymce.get('article-editor').getContent() : form.querySelector('[name=content]').value;

    // DOMPurify 过滤
    if (typeof DOMPurify !== 'undefined') {
        content = DOMPurify.sanitize(content, {
            ALLOWED_TAGS: ['p', 'br', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'strong', 'em', 'u', 's', 'a', 'img', 'blockquote', 'ul', 'ol', 'li', 'pre', 'code', 'hr', 'div', 'span', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'figure', 'figcaption'],
            ALLOWED_ATTR: ['href', 'src', 'alt', 'title', 'class', 'style', 'target', 'rel'],
        });
    }

    const data = {
        id: form.querySelector('[name=id]').value || undefined,
        title: form.querySelector('[name=title]').value,
        content: content,
        cover: form.querySelector('[name=cover]').value,
        status: status
    };

    const method = data.id ? 'PUT' : 'POST';
    const res = await fetch('/admin/api/articles', {
        method,
        headers: csrfHeaders({ 'Content-Type': 'application/json' }),
        body: JSON.stringify(data)
    });

    const result = await res.json();
    if (result.code === 200 || result.code === 201) {
        showToast('文章保存成功', 'success');
        if (!data.id && result.data?.id) {
            setTimeout(() => { window.location.href = '/admin/articles/edit/' + result.data.id; }, 500);
        }
    } else {
        showToast(result.message || '保存失败', 'error');
    }
}

// Ctrl+S 快捷键
document.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        saveArticle('draft');
    }
});

// 状态选择交互
document.querySelectorAll('input[name="status"]').forEach(radio => {
    radio.addEventListener('change', () => {
        document.querySelectorAll('input[name="status"]').forEach(r => {
            const label = r.closest('label');
            if (r.checked) {
                label.classList.add('bg-' + r.value + '/10');
            } else {
                label.classList.remove('bg-' + r.value + '/10');
            }
        });
    });
});
</script>
