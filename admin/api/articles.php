<?php
// 后台文章 API
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/auth_helper.php';

$db = getDB();
$user = requireAuth();
$method = getMethod();

if ($method !== 'GET') verifyCsrfToken();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$id = null;
if (preg_match('/\/(\d+)\/?$/', $path, $m)) {
    $id = (int)$m[1];
}

// author 角色数据过滤
$authorFilter = '';
$authorParams = [];
if ($user['role'] === 'author') {
    $authorFilter = ' AND author_id = :author_id';
    $authorParams[':author_id'] = $user['id'];
}

switch ($method) {
    case 'GET':
        if ($id) {
            $sql = "SELECT a.*, u.username AS author_name FROM articles a LEFT JOIN users u ON a.author_id = u.id WHERE a.id = :id" . $authorFilter;
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id);
            foreach ($authorParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $item = $stmt->fetch();
            if (!$item) jsonResponse(3001, '文章不存在');
            jsonResponse(200, 'success', $item);
        }

        [$page, $per, $offset] = getPagination();
        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $where = 'WHERE 1=1';
        $params = [];
        if ($search) {
            $where .= " AND a.title LIKE :search";
            $params[':search'] = "%$search%";
        }
        if ($status && in_array($status, ['draft','published','pending','archived'])) {
            $where .= " AND a.status = :status";
            $params[':status'] = $status;
        }
        $where .= $authorFilter;
        $params = array_merge($params, $authorParams);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM articles a $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT a.id, a.title, a.cover, a.status, a.created_at, a.updated_at, u.username AS author_name FROM articles a LEFT JOIN users u ON a.author_id = u.id $where ORDER BY a.created_at DESC LIMIT :per OFFSET :offset");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':per', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll();

        jsonResponse(200, 'success', ['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
        break;

    case 'POST':
        $body = getJsonBody();
        $title = trim($body['title'] ?? '');
        if (empty($title)) jsonResponse(400, '标题不能为空');

        $content = $body['content'] ?? '';
        $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><strong><em><u><s><a><img><blockquote><ul><ol><li><pre><code><hr><div><span><table><thead><tbody><tr><th><td><figure><figcaption>';
        $content = strip_tags($content, $allowedTags);

        // author 只能创建草稿
        $status = $body['status'] ?? 'draft';
        if ($user['role'] === 'author' && $status !== 'draft') {
            $status = 'pending'; // author 只能提交审核
        }

        $stmt = $db->prepare("INSERT INTO articles (title, content, cover, status, author_id, created_at, updated_at) VALUES (:title, :content, :cover, :status, :author_id, NOW(), NOW())");
        $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':cover' => $body['cover'] ?? '',
            ':status' => $status,
            ':author_id' => $user['id'],
        ]);

        $newId = (int)$db->lastInsertId();
        writeLog('create', 'article', $newId, "创建文章: $title");
        jsonResponse(201, '创建成功', ['id' => $newId]);
        break;

    case 'PUT':
        if (!$id) jsonResponse(400, '缺少 ID');
        $body = getJsonBody();
        $title = trim($body['title'] ?? '');
        if (empty($title)) jsonResponse(400, '标题不能为空');

        $content = $body['content'] ?? '';
        $allowedTags = '<p><br><h1><h2><h3><h4><h5><h6><strong><em><u><s><a><img><blockquote><ul><ol><li><pre><code><hr><div><span><table><thead><tbody><tr><th><td><figure><figcaption>';
        $content = strip_tags($content, $allowedTags);

        $fields = ['title = :title', 'content = :content', 'updated_at = NOW()'];
        $params = [':title' => $title, ':content' => $content];

        // author 不能直接发布
        if (isset($body['status'])) {
            $newStatus = $body['status'];
            if ($user['role'] === 'author' && !in_array($newStatus, ['draft', 'pending'])) {
                $newStatus = 'pending';
            }
            $fields[] = 'status = :status';
            $params[':status'] = $newStatus;
        }

        if (isset($body['cover'])) {
            $fields[] = 'cover = :cover';
            $params[':cover'] = $body['cover'];
        }

        $params[':id'] = $id;
        $params = array_merge($params, $authorParams);

        $sql = "UPDATE articles SET " . implode(', ', $fields) . " WHERE id = :id" . $authorFilter;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        writeLog('update', 'article', $id, "更新文章: $title");
        jsonResponse(200, '更新成功');
        break;

    case 'DELETE':
        if (!$id) jsonResponse(400, '缺少 ID');
        // 永久删除
        $sql = "DELETE FROM articles WHERE id = :id" . $authorFilter;
        $params = array_merge([':id' => $id], $authorParams);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        writeLog('delete', 'article', $id, '永久删除文章');
        jsonResponse(200, '删除成功');
        break;

    default:
        jsonResponse(405, '方法不允许');
}
