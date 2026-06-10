<?php
// 暮想 Museve 数据库连接与自动建表
$config = require __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        global $config;
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['db']['host'],
            $config['db']['port'],
            $config['db']['dbname'],
            $config['db']['charset']
        );
        try {
            $pdo = new PDO($dsn, $config['db']['username'], $config['db']['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES    => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'code' => 500,
                'message' => '数据库连接失败',
                'data' => null
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    return $pdo;
}

function autoCreateTables(PDO $db): void {
    // memories 回忆表
    $db->exec("
        CREATE TABLE IF NOT EXISTS `memories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `image` VARCHAR(500) NOT NULL DEFAULT '',
            `event_time` DATETIME NOT NULL,
            `author_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_author_id` (`author_id`),
            INDEX `idx_event_time` (`event_time`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // whispers 悄悄话表
    $db->exec("
        CREATE TABLE IF NOT EXISTS `whispers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `content` TEXT NOT NULL,
            `author_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_author_id` (`author_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // articles 文章表
    $db->exec("
        CREATE TABLE IF NOT EXISTS `articles` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `content` LONGTEXT NOT NULL,
            `cover` VARCHAR(500) NOT NULL DEFAULT '',
            `status` ENUM('draft','published','pending','archived','deleted') DEFAULT 'draft',
            `author_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_author_id` (`author_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // users 用户表
    $db->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `account` VARCHAR(50) NOT NULL UNIQUE,
            `username` VARCHAR(50) NOT NULL DEFAULT '',
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('admin','author') DEFAULT 'author',
            `avatar` VARCHAR(500) NOT NULL DEFAULT '',
            `cookie_token` VARCHAR(255) NOT NULL DEFAULT '',
            `token_expires` DATETIME NULL,
            `last_login` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_cookie_token` (`cookie_token`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // settings 设置表
    $db->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 首次建表后插入默认数据
    $stmt = $db->query("SELECT COUNT(*) AS cnt FROM `settings`");
    $row = $stmt->fetch();
    if ($row['cnt'] == 0) {
        // 默认设置项
        $defaults = [
            'site_title'   => '暮想',
            'site_subtitle' => '在薄暮时分，温柔地想起。',
            'site_avatar'  => '/resources/images/default-avatar.png',
            'site_logo'    => '/resources/images/logo.svg',
            'quote_1'      => '时光会走远，记忆会永恒。',
            'quote_2'      => '每一段回忆，都值得被温柔珍藏。',
            'quote_3'      => '在薄暮时分，想起那些温暖的瞬间。',
            'icp_number'   => '',
            'copyright'    => '© 2026 暮想 Museve',
        ];
        $stmt = $db->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)");
        foreach ($defaults as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        // 创建默认管理员 (admin / admin123)
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("INSERT IGNORE INTO `users` (`account`, `username`, `password`, `role`) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', '管理员', $adminPassword, 'admin']);

        // 创建上传目录
        $dirs = [__DIR__ . '/../uploads/memories', __DIR__ . '/../uploads/avatars'];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }
}

// 自动建表
autoCreateTables(getDB());

// 统一 JSON 响应函数
function jsonResponse(int $code, string $message, $data = null): void {
    http_response_code($code >= 400 ? $code : 200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 获取请求方法
function getMethod(): string {
    return $_SERVER['REQUEST_METHOD'];
}

// 获取 JSON 请求体
function getJsonBody(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// 获取分页参数
function getPagination(int $defaultPer = 10): array {
    $page = max(1, intval($_GET['page'] ?? 1));
    $per = min(50, max(1, intval($_GET['per'] ?? $defaultPer)));
    $offset = ($page - 1) * $per;
    return [$page, $per, $offset];
}

// 获取路由 ID 参数
function getRouteId(): int {
    return intval($_GET['id'] ?? 0);
}

// 统一错误码常量
const ERR_INVALID_CREDENTIALS = 2000;
const ERR_ACCOUNT_LOCKED = 2001;
const ERR_CAPTCHA_ERROR = 2002;
const ERR_ARTICLE_NOT_FOUND = 3001;
const ERR_NO_PERMISSION = 3002;
const ERR_UPLOAD_FAILED = 4001;
const ERR_FILE_TYPE_NOT_ALLOWED = 4002;
