<?php
// 暮想 Museve 数据库连接
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
            echo json_encode(['error' => '数据库连接失败', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    return $pdo;
}

function autoCreateTables(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `memories` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `image` VARCHAR(500) NOT NULL DEFAULT '',
            `event_time` DATETIME NULL,
            `author_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `whispers` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `content` TEXT NOT NULL,
            `author_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `articles` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `content` LONGTEXT NOT NULL,
            `status` ENUM('draft','published','pending','archived','deleted') DEFAULT 'draft',
            `author_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `updated_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

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
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `last_login` DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 首次建表后插入默认设置
    $stmt = $db->query("SELECT COUNT(*) AS cnt FROM `settings`");
    $row = $stmt->fetch();
    if ($row['cnt'] == 0) {
        $db->exec("INSERT INTO `settings` (`key`, `value`) VALUES ('site_title', '暮想')");
        $db->exec("INSERT INTO `settings` (`key`, `value`) VALUES ('site_subtitle', '在薄暮时分，温柔地想起。')");

        // 创建默认管理员
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("INSERT IGNORE INTO `users` (`account`, `username`, `password`, `role`) VALUES (?, ?, ?, ?)");
        $stmt->execute(['admin', 'Admin', $adminPassword, 'admin']);
    }
}

// 自动建表
autoCreateTables(getDB());
