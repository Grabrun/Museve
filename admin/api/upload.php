<?php
// 后台 - 文件上传 API
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/auth_helper.php';

$user = requireAuth();
$method = getMethod();

if ($method !== 'POST') jsonResponse(405, '方法不允许');

if (empty($_FILES['file'])) jsonResponse(400, '没有上传文件');

$file = $_FILES['file'];
$config = require __DIR__ . '/../../includes/config.php';

// 验证文件类型（双重校验：客户端 MIME + 服务端 magic bytes）
$allowedTypes = $config['upload']['allowed_types'];
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExtensions)) {
    jsonResponse(4002, '文件类型不允许');
}

// 服务端 MIME 验证（防止客户端伪造）
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$realMime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($realMime, $allowedTypes)) {
    jsonResponse(4002, '文件类型不允许');
}

if (!in_array($file['type'], $allowedTypes)) {
    jsonResponse(4002, '文件类型不允许');
}

// 验证文件大小
if ($file['size'] > $config['upload']['max_size']) {
    jsonResponse(4001, '文件过大，最大 5MB');
}

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
