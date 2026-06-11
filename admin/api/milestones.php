<?php
// 后台里程碑 API
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

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $db->prepare("SELECT * FROM milestones WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();
            if (!$item) jsonResponse(404, '里程碑不存在');
            jsonResponse(200, 'success', $item);
        }

        [$page, $per, $offset] = getPagination(20);
        $countStmt = $db->query("SELECT COUNT(*) FROM milestones");
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("SELECT * FROM milestones ORDER BY sort_order ASC LIMIT :per OFFSET :offset");
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

        $date = $body['date'] ?? date('Y-m');
        $description = $body['description'] ?? '';
        $icon = $body['icon'] ?? 'ph-flower-tulip';
        $sortOrder = intval($body['sort_order'] ?? 0);

        $stmt = $db->prepare("INSERT INTO milestones (date, title, description, icon, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$date, $title, $description, $icon, $sortOrder]);

        $newId = (int)$db->lastInsertId();
        writeLog('create', 'milestone', $newId, "创建里程碑: $title");
        jsonResponse(201, '创建成功', ['id' => $newId]);
        break;

    case 'PUT':
        if (!$id) jsonResponse(400, '缺少 ID');
        $body = getJsonBody();
        $title = trim($body['title'] ?? '');
        if (empty($title)) jsonResponse(400, '标题不能为空');

        $stmt = $db->prepare("UPDATE milestones SET date=?, title=?, description=?, icon=?, sort_order=? WHERE id=?");
        $stmt->execute([
            $body['date'] ?? '',
            $title,
            $body['description'] ?? '',
            $body['icon'] ?? 'ph-flower-tulip',
            intval($body['sort_order'] ?? 0),
            $id
        ]);

        writeLog('update', 'milestone', $id, "更新里程碑: $title");
        jsonResponse(200, '更新成功');
        break;

    case 'DELETE':
        if (!$id) jsonResponse(400, '缺少 ID');
        $stmt = $db->prepare("DELETE FROM milestones WHERE id = ?");
        $stmt->execute([$id]);

        writeLog('delete', 'milestone', $id, '删除里程碑');
        jsonResponse(200, '删除成功');
        break;

    default:
        jsonResponse(405, '方法不允许');
}
