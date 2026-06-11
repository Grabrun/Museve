<?php
/**
 * 暮想 Museve — 数据库连接、自动建表与全局工具函数
 *
 * 本文件为项目核心入口，负责：
 *   1. PDO 数据库连接（单例）
 *   2. 自动建表与默认数据初始化
 *   3. 全局工具函数（JSON 响应、分页、日志等）
 *
 * @package Museve
 * @since   1.0.0
 */

// ============================================================
// 1. 配置加载
// ============================================================

$config = require __DIR__ . '/config.php';

// ============================================================
// 2. 建表 SQL 定义
// ============================================================

/**
 * 所有数据表的建表 SQL 语句
 *
 * @var string[]
 */
const TABLE_DEFINITIONS = [
    // 回忆表
    'memories' => "
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
    ",

    // 里程碑表
    'milestones' => "
        CREATE TABLE IF NOT EXISTS `milestones` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `date` VARCHAR(20) NOT NULL DEFAULT '',
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `description` TEXT,
            `icon` VARCHAR(100) NOT NULL DEFAULT 'ph-flower-tulip',
            `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // 悄悄话表
    'whispers' => "
        CREATE TABLE IF NOT EXISTS `whispers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `content` TEXT NOT NULL,
            `signature` VARCHAR(100) NOT NULL DEFAULT '',
            `author_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_author_id` (`author_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // 里程碑表
    'milestones' => "
        CREATE TABLE IF NOT EXISTS `milestones` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `date` VARCHAR(20) NOT NULL DEFAULT '',
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `description` TEXT,
            `icon` VARCHAR(100) NOT NULL DEFAULT 'ph-flower-tulip',
            `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // 文章表
    'articles' => "
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
    ",

    // 里程碑表
    'milestones' => "
        CREATE TABLE IF NOT EXISTS `milestones` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `date` VARCHAR(20) NOT NULL DEFAULT '',
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `description` TEXT,
            `icon` VARCHAR(100) NOT NULL DEFAULT 'ph-flower-tulip',
            `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // 用户表
    'users' => "
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
    ",

    // 里程碑表
    'milestones' => "
        CREATE TABLE IF NOT EXISTS `milestones` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `date` VARCHAR(20) NOT NULL DEFAULT '',
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `description` TEXT,
            `icon` VARCHAR(100) NOT NULL DEFAULT 'ph-flower-tulip',
            `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // 设置表
    'settings' => "
        CREATE TABLE IF NOT EXISTS `settings` (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // 里程碑表
    'milestones' => "
        CREATE TABLE IF NOT EXISTS `milestones` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `date` VARCHAR(20) NOT NULL DEFAULT '',
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `description` TEXT,
            `icon` VARCHAR(100) NOT NULL DEFAULT 'ph-flower-tulip',
            `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_sort` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    // 操作日志表
    'logs' => "
        CREATE TABLE IF NOT EXISTS `logs` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `action` VARCHAR(50) NOT NULL,
            `target_type` VARCHAR(50) NOT NULL DEFAULT '',
            `target_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `detail` TEXT,
            `ip` VARCHAR(45) NOT NULL DEFAULT '',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_action` (`action`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
];

/**
 * 首次建表后插入的默认设置项
 *
 * @var array<string, string>
 */
const DEFAULT_SETTINGS = [
    'site_title'    => '暮想',
    'site_subtitle' => '在薄暮时分，温柔地想起。',
    'site_description' => '',
    'site_keywords' => '暮想, Museve, 回忆, 悄悄话, 文章',
    'site_avatar'   => '/resources/images/default-avatar.png',
    'site_logo'     => '/resources/images/logo.svg',
    'home_avatar'   => '',
    'quote_1'       => '时光会走远，记忆会永恒。',
    'quote_2'       => '每一段回忆，都值得被温柔珍藏。',
    'quote_3'       => '在薄暮时分，想起那些温暖的瞬间。',
    'icp_number'    => '',
    'copyright'     => '© 2026 暮想 Museve',
    'custom_footer' => '',
    'upload_max_size' => '5',
    'upload_allowed_types' => 'image/jpeg,image/png,image/gif,image/webp',
    'cdn_prefix' => '',
    'login_token_expiry' => '30',
    'maintenance_mode' => '0',
];

// ============================================================
// 3. 统一错误码常量
// ============================================================

/** 认证凭证错误 */
const ERR_INVALID_CREDENTIALS = 2000;
/** 账号被锁定 */
const ERR_ACCOUNT_LOCKED      = 2001;
/** 验证码错误 */
const ERR_CAPTCHA_ERROR       = 2002;
/** 文章不存在 */
const ERR_ARTICLE_NOT_FOUND   = 3001;
/** 无权限 */
const ERR_NO_PERMISSION       = 3002;
/** 上传失败 */
const ERR_UPLOAD_FAILED       = 4001;
/** 文件类型不允许 */
const ERR_FILE_TYPE_NOT_ALLOWED = 4002;

// ============================================================
// 4. 工具函数
// ============================================================

/**
 * 获取当前请求方法（大写）
 *
 * @return string 例如 'GET'、'POST'
 */
function getMethod(): string {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * 获取 JSON 请求体并解码为数组（缓存读取，防止多次调用 php://input）
 *
 * @return array 解码后的关联数组，失败时返回空数组
 */
function getJsonBody(): array {
    static $cached = null;
    if ($cached !== null) return $cached;
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    $cached = is_array($data) ? $data : [];
    return $cached;
}

/**
 * 解析分页参数
 *
 * @param  int   $defaultPer 默认每页条数
 * @return array [page, per, offset]
 */
function getPagination(int $defaultPer = 10): array {
    $page   = max(1, intval($_GET['page'] ?? 1));
    $per    = min(50, max(1, intval($_GET['per'] ?? $defaultPer)));
    $offset = ($page - 1) * $per;
    return [$page, $per, $offset];
}

/**
 * 获取路由中的 ID 参数
 *
 * @return int 参数值，不存在时返回 0
 */
function getRouteId(): int {
    return intval($_GET['id'] ?? 0);
}

// ============================================================
// 5. JSON 响应函数
// ============================================================

/**
 * 统一 JSON 响应并终止脚本
 *
 * @param int         $code    HTTP 状态码 / 业务码
 * @param string      $message 提示信息
 * @param mixed       $data    响应数据
 * @return never
 */
function jsonResponse(int $code, string $message, $data = null): never {
    http_response_code($code >= 400 ? $code : 200);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code'    => $code,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 成功响应快捷方法
 *
 * @param mixed  $data    响应数据
 * @param string $message 提示信息
 * @return never
 */
function jsonSuccess($data = null, string $message = 'success'): never {
    jsonResponse(200, $message, $data);
}

// ============================================================
// 6. 数据库连接
// ============================================================

/**
 * 获取 PDO 数据库连接（单例）
 *
 * @global array $config 全局配置
 * @return PDO
 */
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
                'code'    => 500,
                'message' => '数据库连接失败',
                'data'    => null,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    return $pdo;
}

// ============================================================
// 7. 建表与初始化
// ============================================================

/**
 * 自动创建所有数据表并在首次运行时插入默认数据
 *
 * @param  PDO  $db 数据库连接
 * @return void
 */
function autoCreateTables(PDO $db): void {
    // 创建所有数据表
    foreach (TABLE_DEFINITIONS as $name => $sql) {
        $db->exec($sql);
    }

    // 首次建表后插入默认数据
    $stmt = $db->query("SELECT COUNT(*) AS cnt FROM `settings`");
    $row  = $stmt->fetch();
    if ($row['cnt'] == 0) {
        // 插入默认设置
        $insertStmt = $db->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)");
        foreach (DEFAULT_SETTINGS as $key => $value) {
            $insertStmt->execute([$key, $value]);
        }

        // 创建默认管理员 (admin / admin123)
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $adminStmt = $db->prepare("INSERT IGNORE INTO `users` (`account`, `username`, `password`, `role`) VALUES (?, ?, ?, ?)");
        $adminStmt->execute(['admin', '管理员', $adminPassword, 'admin']);

        // 创建上传目录
        $dirs = [__DIR__ . '/../uploads/memories', __DIR__ . '/../uploads/avatars'];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
        }
    }
}

// 执行自动建表
autoCreateTables(getDB());

// ============================================================
// 8. 日志工具
// ============================================================

/**
 * 写入操作日志
 *
 * @param  string $action     操作类型
 * @param  string $targetType 目标类型
 * @param  int    $targetId   目标 ID
 * @param  string $detail     详情描述
 * @return void
 */
function writeLog(string $action, string $targetType = '', int $targetId = 0, string $detail = ''): void {
    try {
        $db     = getDB();
        $userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt   = $db->prepare(
            "INSERT INTO logs (user_id, action, target_type, target_id, detail, ip) VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$userId, $action, $targetType, $targetId, $detail, $ip]);
    } catch (PDOException $e) {
        // 日志写入失败不应中断主流程
        error_log('[Museve] writeLog failed: ' . $e->getMessage());
    }
}
