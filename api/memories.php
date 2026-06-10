<?php
// 回忆 API (公开)
require_once __DIR__ . '/../includes/connect.php';

$method = getMethod();

if ($method === 'GET') {
    [$page, $per, $offset] = getPagination();

    $countStmt = getDB()->query("SELECT COUNT(*) FROM memories");
    $total = (int)$countStmt->fetchColumn();

    $stmt = getDB()->prepare("
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
}

jsonResponse(405, '方法不允许');
