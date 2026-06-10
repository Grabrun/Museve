<?php
// 后台文章 API
require_once __DIR__ . '/auth_helper.php';

$pdo = getDB();
$method = getMethod();
$user = requireAdmin();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$id = null;
if (preg_match('/\/(\d+)\/?$/', $path, $m)) {
    $id = (int)$m[1];
}

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT a.*, u.nickname AS author_name FROM articles a LEFT JOIN users u ON a.user_id = u.id WHERE a.id = :id");
            $stmt->execute([':id' => $id]);
            $item = $stmt->fetch();
            if (!$item) {
                jsonResponse(ERR_ARTICLE_NOT_FOUND, '文章不存在');
            }
            jsonSuccess($item);
        }

        [$page, $per, $offset] = getPagination();
        $search = getParam('search', '');
        $status = getParam('status', '');

        $where = [];
        $params = [];
        if (!empty($search)) {
            $where[] = "a.title LIKE :search";
            $params[':search'] = "%{$search}%";
        }
        if (!empty($status)) {
            $where[] = "a.status = :status";
            $params[':status'] = $status;
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM articles a {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT a.id, a.title, a.cover, a.status, a.created_at, a.updated_at, u.nickname AS author_name FROM articles a LEFT JOIN users u ON a.user_id = u.id {$whereClause} ORDER BY a.created_at DESC LIMIT :per OFFSET :offset");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':per', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll();

        jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
        break;

    case 'POST':
        $body = getJsonBody();
        $title = trim($body['title'] ?? '');
        if (empty($title)) {
            jsonResponse(400, '标题不能为空');
        }

        $content = $body['content'] ?? '';
        // 保留基本 HTML 标签，过滤危险标签
        $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><strong><em><u><s><a><img><blockquote><ul><ol><li><pre><code><hr><div><span><table><thead><tbody><tr><th><td><figure><figcaption><iframe>';
        $content = strip_tags($content, $allowedTags);

        $stmt = $pdo->prepare("INSERT INTO articles (title, content, cover, status, user_id, created_at, updated_at) VALUES (:title, :content, :cover, :status, :user_id, NOW(), NOW())");
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':cover' => $body['cover'] ?? '',
            ':status' => $body['status'] ?? 'draft',
            ':user_id' => $user['id'],
        ]);

        jsonSuccess(['id' => (int)$pdo->lastInsertId()]);
        break;

    case 'PUT':
        if (!$id) {
            jsonResponse(400, '缺少 ID');
        }
        $body = getJsonBody();
        $title = trim($body['title'] ?? '');
        if (empty($title)) {
            jsonResponse(400, '标题不能为空');
        }

        $content = $body['content'] ?? '';
        $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><strong><em><u><s><a><img><blockquote><ul><ol><li><pre><code><hr><div><span><table><thead><tbody><tr><th><td><figure><figcaption><iframe>';
        $content = strip_tags($content, $allowedTags);

        $fields = ['title = :title', 'content = :content', 'status = :status', 'updated_at = NOW()'];
        $params = [
            ':title' => $title,
            ':content' => $content,
            ':status' => $body['status'] ?? 'draft',
        ];
        if (isset($body['cover'])) {
            $fields[] = 'cover = :cover';
            $params[':cover'] = $body['cover'];
        }
        $params[':id'] = $id;

        $stmt = $pdo->prepare("UPDATE articles SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($params);

        jsonSuccess(['affected' => $stmt->rowCount()]);
        break;

    case 'DELETE':
        if (!$id) {
            jsonResponse(400, '缺少 ID');
        }
        $stmt = $pdo->prepare("DELETE FROM articles WHERE id = :id");
        $stmt->execute([':id' => $id]);
        jsonSuccess(['affected' => $stmt->rowCount()]);
        break;

    default:
        jsonResponse(405, 'Method Not Allowed');
}
