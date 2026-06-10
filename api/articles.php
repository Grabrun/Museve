<?php
// 文章 API
require_once __DIR__ . '/../includes/db.php';

$pdo = getDB();
$method = getMethod();

if ($method === 'GET') {
    // 检查是否请求单篇文章
    $id = getRouteId();
    if ($id) {
        $stmt = $pdo->prepare("SELECT a.*, u.nickname AS author_name FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = :id AND a.status = 'published'");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $article = $stmt->fetch();
        if ($article) {
            jsonSuccess($article);
        }
        jsonResponse(ERR_ARTICLE_NOT_FOUND, '文章不存在');
    }

    // 列表
    [$page, $per, $offset] = getPagination();

    $countStmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT a.id, a.title, a.cover, a.status, a.created_at, u.nickname AS author_name FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.status = 'published' ORDER BY a.created_at DESC LIMIT :per OFFSET :offset");
    $stmt->bindValue(':per', $per, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $list = $stmt->fetchAll();

    jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
}

jsonResponse(405, 'Method Not Allowed');
