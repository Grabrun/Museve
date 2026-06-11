<?php
/**
 * 悄悄话 API (公开)
 * 获取悄悄话列表，关联作者信息
 */
require_once __DIR__ . '/../includes/connect.php';

$method = getMethod();

if ($method === 'GET') {
    try {
        [$page, $per, $offset] = getPagination(15);

        $db = getDB();

        $countStmt = $db->query("SELECT COUNT(*) FROM whispers");
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT w.id, w.content, w.signature, w.created_at,
                   u.username, u.avatar
            FROM whispers w
            LEFT JOIN users u ON w.author_id = u.id
            ORDER BY w.created_at DESC
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
