<?php
// 回忆 API
require_once __DIR__ . '/../includes/connect.php';

$pdo = getDB();
$method = getMethod();

if ($method === 'GET') {
    [$page, $per, $offset] = getPagination();

    $countStmt = $pdo->query("SELECT COUNT(*) FROM memories");
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM memories ORDER BY event_time DESC LIMIT :per OFFSET :offset");
    $stmt->bindValue(':per', $per, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll();

    jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
}

jsonResponse(405, 'Method Not Allowed');
