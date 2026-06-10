<?php
// 后台悄悄话 API
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
            $stmt = $pdo->prepare("SELECT w.*, u.username, u.avatar FROM whispers w LEFT JOIN users u ON w.author_id = u.id WHERE w.id = :id");
            $stmt->execute([':id' => $id]);
            $item = $stmt->fetch();
            if (!$item) {
                jsonResponse(404, '悄悄话不存在');
            }
            jsonSuccess($item);
        }

        [$page, $per, $offset] = getPagination();

        $countStmt = $pdo->query("SELECT COUNT(*) FROM whispers");
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT w.*, u.username, u.avatar FROM whispers w LEFT JOIN users u ON w.author_id = u.id ORDER BY w.created_at DESC LIMIT :per OFFSET :offset");
        $stmt->bindValue(':per', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll();

        jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
        break;

    case 'POST':
        $body = getJsonBody();
        $content = trim($body['content'] ?? '');
        if (empty($content)) {
            jsonResponse(400, '内容不能为空');
        }

        $stmt = $pdo->prepare("INSERT INTO whispers (content, user_id, created_at) VALUES (:content, :author_id, NOW())");
        $stmt->execute([
            ':content' => $content,
            ':author_id' => $user['id'],
        ]);

        jsonSuccess(['id' => (int)$pdo->lastInsertId()]);
        break;

    case 'PUT':
        if (!$id) {
            jsonResponse(400, '缺少 ID');
        }
        $body = getJsonBody();
        $content = trim($body['content'] ?? '');
        if (empty($content)) {
            jsonResponse(400, '内容不能为空');
        }

        $stmt = $pdo->prepare("UPDATE whispers SET content = :content WHERE id = :id");
        $stmt->execute([
            ':content' => $content,
            ':id' => $id,
        ]);

        jsonSuccess(['affected' => $stmt->rowCount()]);
        break;

    case 'DELETE':
        if (!$id) {
            jsonResponse(400, '缺少 ID');
        }
        $stmt = $pdo->prepare("DELETE FROM whispers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        jsonSuccess(['affected' => $stmt->rowCount()]);
        break;

    default:
        jsonResponse(405, 'Method Not Allowed');
}
