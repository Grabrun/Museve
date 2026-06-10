<?php
// 设置 API (公开)
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
$method = getMethod();

if ($method === 'GET') {
    $publicKeys = ['site_title', 'site_subtitle', 'site_avatar', 'site_logo', 'site_favicon', 'icp_beian', 'police_beian', 'quotes'];
    $placeholders = implode(',', array_fill(0, count($publicKeys), '?'));

    $stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)");
    $stmt->execute($publicKeys);
    $rows = $stmt->fetchAll();

    $settings = [];
    foreach ($rows as $row) {
        $settings[$row['key']] = $row['value'];
    }

    jsonSuccess($settings);
}

jsonResponse(405, 'Method Not Allowed');
