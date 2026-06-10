<?php
/**
 * 设置 API (公开)
 * 获取公开的网站设置
 */
require_once __DIR__ . '/../includes/connect.php';

$method = getMethod();

if ($method === 'GET') {
    try {
        $db = getDB();

        $publicKeys = ['site_title', 'site_subtitle', 'site_avatar', 'site_logo', 'icp_number', 'copyright', 'quote_1', 'quote_2', 'quote_3'];

        $placeholders = implode(',', array_fill(0, count($publicKeys), '?'));
        $stmt = $db->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)");
        $stmt->execute($publicKeys);

        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['key']] = $row['value'];
        }

        jsonSuccess($settings);
    } catch (PDOException $e) {
        jsonResponse(500, '服务器错误，请稍后重试');
    }
}

jsonResponse(405, '方法不允许');
