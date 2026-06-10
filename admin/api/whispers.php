<?php
// 后台悄悄话 API
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

$authorFilter = '';
$authorParams = [];
if ($user['role'] === 'author') {
    $authorFilter = ' AND w.author_id = :author_id';
    $authorParams[':author_id'] = $user['id'];
}

switch ($method) {
    case 'GET':
        if ($id) {
            $sql = "SELECT w.*, u.username, u.avatar FROM whispers w LEFT JOIN users u ON w.author_id = u.id WHERE w.id = :id" . $authorFilter;
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id);
            foreach ($authorParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $item = $stmt->fetch();
            if (!$item) jsonResponse(404, '悄悄话不存在');
            jsonResponse(200, 'success', $item);
        }

        [$page, $per, $offset] = getPagination(15);

        $where = 'WHERE 1=1' . $authorFilter;
        $params = $authorParams;

        $countStmt = $db->prepare("SELECT COUNT(*) FROM whispers w $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT w.*, u.username, u.avatar FROM whispers w LEFT JOIN users u ON w.author_id = u.id $where ORDER BY w.created_at DESC LIMIT :per OFFSET :offset");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':per', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll();

        jsonResponse(200, 'success', ['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
        break;

    case 'POST':
        $body = getJsonBody();
        $content = trim($body['content'] ?? '');
        if (empty($content)) jsonResponse(400, '内容不能为空');

        $stmt = $db->prepare("INSERT INTO whispers (content, author_id, created_at) VALUES (:content, :author_id, NOW())");
        $stmt->execute([
            ':content' => $content,
            ':author_id' => $user['id'],
        ]);

        jsonResponse(201, '创建成功', ['id' => (int)$db->lastInsertId()]);
        break;

    case 'PUT':
        if (!$id) jsonResponse(400, '缺少 ID');
        $body = getJsonBody();
        $content = trim($body['content'] ?? '');
        if (empty($content)) jsonResponse(400, '内容不能为空');

        $sql = "UPDATE whispers SET content = :content, updated_at = NOW() WHERE id = :id" . $authorFilter;
        $params = array_merge([':content' => $content, ':id' => $id], $authorParams);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(200, '更新成功');
        break;

    case 'DELETE':
        if (!$id) jsonResponse(400, '缺少 ID');
        $sql = "DELETE FROM whispers WHERE id = :id" . $authorFilter;
        $params = array_merge([':id' => $id], $authorParams);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        jsonResponse(200, '删除成功');
        break;

    default:
        jsonResponse(405, '方法不允许');
}
