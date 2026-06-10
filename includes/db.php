<?php
// 数据库连接
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

/**
 * 统一 JSON 响应
 */
function jsonResponse(int $code, string $message, $data = null): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 成功响应
 */
function jsonSuccess($data = null): void {
    jsonResponse(200, 'success', $data);
}

/**
 * 获取请求方法
 */
function getMethod(): string {
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/**
 * 获取 JSON 请求体
 */
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * 获取 GET 参数
 */
function getParam(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

/**
 * 获取路由中的 {id} 参数 (从 path info 解析)
 */
function getRouteId(): ?int {
    $path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '';
    // 匹配末尾数字 ID
    if (preg_match('/\/(\d+)\/?$/', $path, $m)) {
        return (int)$m[1];
    }
    // 也尝试从 GET 参数获取
    if (isset($_GET['id'])) {
        return (int)$_GET['id'];
    }
    return null;
}

/**
 * 获取分页参数
 */
function getPagination(): array {
    $page = max(1, (int)(getParam('page', 1)));
    $per = min(100, max(1, (int)(getParam('per', 10))));
    $offset = ($page - 1) * $per;
    return [$page, $per, $offset];
}
