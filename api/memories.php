<?php
/**
 * 回忆 API (公开)
 * 获取回忆列表，关联作者信息
 */
require_once __DIR__ . '/../includes/connect.php';

$method = getMethod();

if ($method === 'GET') {
    try {
        [$page, $per, $offset] = getPagination();

        $db = getDB();

        $countStmt = $db->query("SELECT COUNT(*) FROM memories");
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT m.id, m.title, m.image, m.event_time, m.created_at,
                   u.username AS author_name
            FROM memories m
            LEFT JOIN users u ON m.author_id = u.id
            ORDER BY m.event_time DESC
            LIMIT :per OFFSET :offset
        ");
        $stmt->bindValue(':per', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll();

        jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
    } catch (PDOException $e) {
        jsonResponse(500, '服务器错误，请稍后重试');
    }
}

jsonResponse(405, '方法不允许');
