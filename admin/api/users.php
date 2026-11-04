<?php
// 后台 API - 用户管理
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/auth_helper.php';

$currentUser = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// 非 GET 请求验证 CSRF
if ($method !== 'GET') verifyCsrfToken();

// 整个用户管理 API 仅管理员可访问
if ($currentUser['role'] !== 'admin') {
    jsonResponse(403, '无权限');
}

// 从 URL 路径解析 ID
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$urlId = 0;
if (preg_match('/admin\/api\/users\/(\d+)/', $path, $m)) {
    $urlId = (int)$m[1];
}

/**
 * 统计除指定用户之外的剩余管理员数量
 *
 * 用于阻止"把最后一位管理员降级/删除"导致全站失去管理入口（自锁）。
 *
 * @param  PDO $db
 * @param  int $excludeId 要排除的用户 ID
 * @return int
 */
function remainingAdmins(PDO $db, int $excludeId): int {
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND id <> ?");
    $stmt->execute([$excludeId]);
    return (int)$stmt->fetchColumn();
}

switch ($method) {
    case 'GET':
        $page = max(1, intval($_GET['page'] ?? 1));
        $per = min(50, max(1, intval($_GET['per'] ?? 10)));
        $offset = ($page - 1) * $per;

        $total = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stmt = $db->prepare("SELECT id, account, username, role, avatar, created_at, last_login FROM users ORDER BY id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(200, 'success', ['list' => $list, 'total' => (int)$total, 'page' => $page, 'per' => $per]);
        break;

    case 'POST':
        $data = getJsonBody();
        $account = trim($data['account'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $role = in_array($data['role'] ?? '', ['admin', 'author']) ? $data['role'] : 'author';

        if (!$account || !$username || !$password) {
            jsonResponse(400, '账号、昵称和密码不能为空');
        }

        if ($pwError = validatePasswordStrength($password)) {
            jsonResponse(400, $pwError);
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE account = ?");
        $stmt->execute([$account]);
        if ($stmt->fetchColumn() > 0) {
            jsonResponse(422, '账号已存在');
        }

        $hashedPassword = hashPassword($password);
        $stmt = $db->prepare("INSERT INTO users (account, username, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$account, $username, $hashedPassword, $role]);

        $newId = (int)$db->lastInsertId();
        writeLog('create', 'user', $newId, "创建用户: $username ($account)");
        jsonResponse(201, '用户创建成功', ['id' => $newId]);
        break;

    case 'PUT':
        $data = getJsonBody();
        $id = intval($data['id'] ?? 0);
        if (!$id) jsonResponse(400, '缺少用户 ID');

        $targetStmt = $db->prepare("SELECT id, account, username, role FROM users WHERE id = ?");
        $targetStmt->execute([$id]);
        $target = $targetStmt->fetch();
        if (!$target) jsonResponse(404, '用户不存在');

        $fields = [];
        $params = [];

        if (!empty($data['username'])) {
            $fields[] = 'username = ?';
            $params[] = $data['username'];
        }

        if (!empty($data['role']) && in_array($data['role'], ['admin', 'author'], true)) {
            // 最后一位管理员不允许被降级，否则全站再没有任何人能进用户管理/设置
            if ($target['role'] === 'admin' && $data['role'] !== 'admin' && remainingAdmins($db, $id) === 0) {
                jsonResponse(403, '这是最后一位管理员，请先提升另一位用户为管理员');
            }
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }

        if (!empty($data['password'])) {
            if ($pwError = validatePasswordStrength($data['password'])) {
                jsonResponse(400, $pwError);
            }
            $fields[] = 'password = ?';
            $params[] = hashPassword($data['password']);
        }

        if (empty($fields)) {
            jsonResponse(400, '没有需要更新的字段');
        }

        $params[] = $id;
        $stmt = $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);

        $logName = $data['username'] ?? $target['username'];
        writeLog('update', 'user', $id, "更新用户: {$logName}");
        jsonResponse(200, '用户更新成功');
        break;

    case 'DELETE':
        $data = getJsonBody();
        $id = $urlId ?: intval($data['id'] ?? $_GET['id'] ?? 0);
        if (!$id) jsonResponse(400, '缺少用户 ID');
        if ($id == $currentUser['id']) jsonResponse(403, '不能删除自己');

        $targetStmt = $db->prepare("SELECT id, role, username FROM users WHERE id = ?");
        $targetStmt->execute([$id]);
        $target = $targetStmt->fetch();
        if (!$target) jsonResponse(404, '用户不存在');

        // 同 PUT：删掉最后一位管理员同样会自锁
        if ($target['role'] === 'admin' && remainingAdmins($db, $id) === 0) {
            jsonResponse(403, '这是最后一位管理员，不能删除');
        }

        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        writeLog('delete', 'user', $id, '删除用户');
        jsonResponse(200, '用户删除成功');
        break;

    default:
        jsonResponse(405, '方法不允许');
}
