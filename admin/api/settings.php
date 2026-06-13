<?php
// 后台 API - 网站设置
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/auth_helper.php';

$currentUser = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// 非 GET 请求验证 CSRF
if ($method !== 'GET') verifyCsrfToken();

// 仅管理员可修改设置
if ($method === 'PUT' && $currentUser['role'] !== 'admin') {
    jsonResponse(403, '无权限，仅管理员可修改设置');
}

switch ($method) {
    case 'GET':
        $stmt = $db->query("SELECT `key`, `value` FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
        jsonResponse(200, 'success', $settings);
        break;

    case 'PUT':
        $data = getJsonBody();
        if (empty($data)) jsonResponse(400, '没有需要更新的数据');

        $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        foreach ($data as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        // 更新缓存版本号，强制静态资源刷新
        $cacheVer = time();
        $stmt->execute(['cache_version', (string)$cacheVer]);

        jsonResponse(200, '设置保存成功');
        break;

    default:
        jsonResponse(405, '方法不允许');
}
