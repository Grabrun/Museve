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
    <h1 class="text-2xl font-serif text-[#3E3640]"><?= $pageTitle ?></h1>
    <a href="/admin/articles" class="text-sm text-[#8E827F] hover:text-[#3E3640]">← 返回列表</a>
</div>

<form id="articleForm" class="space-y-6">
    <input type="hidden" name="id" value="<?= $id ?>">

    <!-- 标题 -->
    <div>
        <input type="text" name="title" value="<?= htmlspecialchars($article['title'] ?? '') ?>" placeholder="文章标题..."
               class="w-full text-2xl font-serif bg-transparent border-b-2 border-[#E5E0DB] focus:border-[#DDB8B8] outline-none pb-2 transition-colors">
    </div>

    <!-- 编辑器 -->
    <div>
        <textarea id="article-editor" name="content" class="w-full min-h-[400px]"><?= htmlspecialchars($article['content'] ?? '') ?></textarea>
    </div>

    <!-- 底部固定保存条 -->
    <div class="fixed bottom-0 left-0 right-0 lg:left-[260px] bg-white/90 backdrop-blur-xl border-t border-[#E5E0DB] px-6 py-4 flex items-center justify-between z-20">
        <div class="flex items-center gap-4">
            <label class="text-sm text-[#8E827F]">状态：</label>
            <select name="status" class="bg-[#F9F7F4] border border-[#E5E0DB] rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:border-[#DDB8B8]">
                <option value="draft" <?= ($article['status'] ?? '') === 'draft' ? 'selected' : '' ?>>草稿</option>
                <option value="published" <?= ($article['status'] ?? '') === 'published' ? 'selected' : '' ?>>发布</option>
                <option value="pending" <?= ($article['status'] ?? '') === 'pending' ? 'selected' : '' ?>>待审核</option>
            </select>
        </div>
        <div class="flex gap-3">
            <button type="button" onclick="saveArticle('draft')"
                    class="px-5 py-2 bg-[#F5F2F0] text-[#3E3640] rounded-lg text-sm hover:bg-[#E5E0DB] transition-colors">保存草稿</button>
            <button type="button" onclick="saveArticle('published')"
                    class="px-5 py-2 bg-[#DDB8B8] text-white rounded-lg text-sm hover:bg-[#B28B8B] transition-colors">发布文章</button>
        </div>
    </div>
</form>

<script>
// TinyMCE 初始化
if (typeof tinymce !== 'undefined') {
    tinymce.init({
        selector: '#article-editor',
        height: 400,
        skin: 'museve',
        content_style: 'body { font-family: "Noto Serif SC", serif; color: #3E3640; line-height: 1.8; font-size: 1.125rem; }',
        plugins: 'lists link image table code codesample',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
        branding: false,
    });
}

async function saveArticle(status) {
    const form = document.getElementById('articleForm');
    const data = {
        id: form.querySelector('[name=id]').value || undefined,
        title: form.querySelector('[name=title]').value,
        content: typeof tinymce !== 'undefined' ? tinymce.get('article-editor').getContent() : form.querySelector('[name=content]').value,
        status: status
    };

    const method = data.id ? 'PUT' : 'POST';
    const res = await fetch('/admin/api/articles', {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });

    const result = await res.json();
    if (result.code === 200 || result.code === 201) {
        showToast('文章保存成功', 'success');
        if (!data.id && result.data?.id) {
            window.location.href = '/admin/articles/edit/' + result.data.id;
        }
    } else {
        showToast(result.message || '保存失败', 'error');
    }
}
</script>
