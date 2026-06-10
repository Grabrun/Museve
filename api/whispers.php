<?php
// 悄悄话 API
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
$method = getMethod();

if ($method === 'GET') {
    [$page, $per, $offset] = getPagination();

    $countStmt = $pdo->query("SELECT COUNT(*) FROM whispers");
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT w.*, u.nickname, u.avatar FROM whispers w LEFT JOIN users u ON w.user_id = u.id ORDER BY w.created_at DESC LIMIT :per OFFSET :offset");
    $stmt->bindValue(':per', $per, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll();

    jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
}

jsonResponse(405, 'Method Not Allowed');
