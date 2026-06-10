<?php
// 后台回忆 API
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/auth_helper.php';

$db = getDB();
$user = requireAuth();
$method = getMethod();

// 非 GET 请求验证 CSRF
if ($method !== 'GET') verifyCsrfToken();

// 解析路由中的 ID
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$id = null;
if (preg_match('/\/(\d+)\/?$/', $path, $m)) {
    $id = (int)$m[1];
}

// author 角色数据过滤
$authorFilter = '';
$authorParams = [];
if ($user['role'] === 'author') {
    $authorFilter = ' AND m.author_id = :author_id';
    $authorParams[':author_id'] = $user['id'];
}

switch ($method) {
    case 'GET':
        if ($id) {
            $sql = "SELECT m.*, u.username AS creator_name FROM memories m LEFT JOIN users u ON m.author_id = u.id WHERE m.id = :id" . $authorFilter;
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $id);
            foreach ($authorParams as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $item = $stmt->fetch();
            if (!$item) jsonResponse(404, '回忆不存在');
            jsonResponse(200, 'success', $item);
        }

        [$page, $per, $offset] = getPagination();
        $search = trim($_GET['search'] ?? '');

        $where = 'WHERE 1=1';
        $params = [];
        if ($search) {
            $where .= " AND m.title LIKE :search";
            $params[':search'] = "%$search%";
        }
        $where .= $authorFilter;
        $params = array_merge($params, $authorParams);

        $countStmt = $db->prepare("SELECT COUNT(*) FROM memories m $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT m.*, u.username AS creator_name FROM memories m LEFT JOIN users u ON m.author_id = u.id $where ORDER BY m.event_time DESC LIMIT :per OFFSET :offset");
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

        $stmt = $db->prepare("INSERT INTO memories (title, image, event_time, author_id, created_at) VALUES (:title, :image, :event_time, :author_id, NOW())");
        $stmt->execute([
            ':title' => $title,
            ':image' => $body['image'] ?? '',
            ':event_time' => $body['event_time'] ?? date('Y-m-d H:i:s'),
            ':author_id' => $user['id'],
        ]);

        $newId = (int)$db->lastInsertId();
        writeLog('create', 'memory', $newId, "创建回忆: $title");
        jsonResponse(201, '创建成功', ['id' => $newId]);
        break;

    case 'PUT':
        if (!$id) jsonResponse(400, '缺少 ID');
        $body = getJsonBody();
        $title = trim($body['title'] ?? '');
        if (empty($title)) jsonResponse(400, '标题不能为空');

        $sql = "UPDATE memories SET title = :title, image = :image, event_time = :event_time, updated_at = NOW() WHERE id = :id" . $authorFilter;
        $params = array_merge([
            ':title' => $title,
            ':image' => $body['image'] ?? '',
            ':event_time' => $body['event_time'] ?? null,
            ':id' => $id,
        ], $authorParams);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        writeLog('update', 'memory', $id, "更新回忆: $title");
        jsonResponse(200, '更新成功');
        break;

    case 'DELETE':
        if (!$id) jsonResponse(400, '缺少 ID');
        $sql = "DELETE FROM memories WHERE id = :id" . $authorFilter;
        $params = array_merge([':id' => $id], $authorParams);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        writeLog('delete', 'memory', $id, '删除回忆');
        jsonResponse(200, '删除成功');
        break;

    default:
        jsonResponse(405, '方法不允许');
}
