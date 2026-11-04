<?php
// 后台 - 文件上传 API
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/auth_helper.php';

$user = requireAuth();
$method = getMethod();

if ($method !== 'POST') jsonResponse(405, '方法不允许');

// 上传是状态变更操作，必须校验 CSRF。
// 前端（含 TinyMCE）通过 X-CSRF-Token 头传递，见 article-edit.php 的 images_upload_handler。
verifyCsrfToken();

if (empty($_FILES['file'])) jsonResponse(400, '没有上传文件');

$file   = $_FILES['file'];
$config = require __DIR__ . '/../../includes/config.php';

// 尺寸与类型以数据库设置为准（后台"上传设置"面板），不再读 config 里被写死的那份
$maxMb = max(1, min(100, (int)getSetting('upload_max_size', '5')));
$maxBytes = $maxMb * 1024 * 1024;

$configured = array_map('trim', explode(',', (string)getSetting('upload_allowed_types', 'image/jpeg,image/png,image/gif,image/webp')));
$configured = array_filter($configured, fn($v) => $v !== '');

// 无论后台填了什么，只认图片 MIME。
// 否则一旦被改成 text/html，上传目录就成了存储型 XSS 的投放点。
$allowedTypes = array_values(array_intersect($configured, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']));
if (!$allowedTypes) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
}

// MIME → 扩展名：完全由服务端决定后缀，不信任 $_FILES['name']
$mimeExt = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
];

// 服务端 magic-bytes 校验。
// 缺 fileinfo 时以前是静默跳过、退回客户端 MIME —— 等于没有校验。
// 现在直接拒绝，让部署方补扩展，而不是默默降低安全等级。
if (!function_exists('finfo_open')) {
    jsonResponse(501, '服务器缺少 fileinfo 扩展，已拒绝上传');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$realMime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
if ($finfo) {
    finfo_close($finfo);
}

if (!is_string($realMime) || !in_array($realMime, $allowedTypes, true)) {
    jsonResponse(4002, '文件类型不允许');
}

if ($file['size'] > $maxBytes) {
    jsonResponse(4001, "文件过大，最大 {$maxMb}MB");
}

$ext = $mimeExt[$realMime] ?? '';

// 随机重命名
$newName = bin2hex(random_bytes(16)) . '.' . $ext;

// 按类型分目录
$isAvatar = strpos($_SERVER['REQUEST_URI'], 'avatar') !== false;
$uploadDir = $isAvatar ? 'avatars' : 'memories';
$targetDir = $config['upload']['upload_dir'] . $uploadDir;

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0775, true);
}

$targetPath = $targetDir . '/' . $newName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    jsonResponse(4001, '上传失败');
}

// 返回相对路径
$url = '/uploads/' . $uploadDir . '/' . $newName;
jsonResponse(200, '上传成功', ['url' => $url, 'name' => $newName]);
