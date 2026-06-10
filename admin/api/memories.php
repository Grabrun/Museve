<?php
// 后台回忆 API
require_once __DIR__ . '/auth_helper.php';

$pdo = getDB();
$method = getMethod();
$user = requireAdmin();

// 解析路由中的 ID
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$id = null;
if (preg_match('/\/(\d+)\/?$/', $path, $m)) {
    $id = (int)$m[1];
}

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("SELECT * FROM memories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $item = $stmt->fetch();
            if (!$item) {
                jsonResponse(404, '回忆不存在');
            }
            jsonSuccess($item);
        }

        [$page, $per, $offset] = getPagination();
        $search = getParam('search', '');

        $where = '';
        $params = [];
        if (!empty($search)) {
            $where = "WHERE title LIKE :search";
            $params[':search'] = "%{$search}%";
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM memories {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT m.*, u.username AS creator_name FROM memories m LEFT JOIN users u ON m.author_id = u.id {$where} ORDER BY m.event_time DESC LIMIT :per OFFSET :offset");
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

        $stmt = $pdo->prepare("INSERT INTO memories (title, image, content, event_time, user_id, created_at) VALUES (:title, :image, :content, :event_time, :author_id, NOW())");
        $stmt->execute([
            ':title' => $title,
            ':image' => $body['image'] ?? '',
            ':content' => $body['content'] ?? '',
            ':event_time' => $body['event_time'] ?? null,
            ':author_id' => $user['id'],
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

        $stmt = $pdo->prepare("UPDATE memories SET title = :title, image = :image, content = :content, event_time = :event_time WHERE id = :id");
        $stmt->execute([
            ':title' => $title,
            ':image' => $body['image'] ?? '',
            ':content' => $body['content'] ?? '',
            ':event_time' => $body['event_time'] ?? null,
            ':id' => $id,
        ]);

        jsonSuccess(['affected' => $stmt->rowCount()]);
        break;

    case 'DELETE':
        if (!$id) {
            jsonResponse(400, '缺少 ID');
        }
        $stmt = $pdo->prepare("DELETE FROM memories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        jsonSuccess(['affected' => $stmt->rowCount()]);
        break;

    default:
        jsonResponse(405, 'Method Not Allowed');
}
