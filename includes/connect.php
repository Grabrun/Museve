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

$configFile = __DIR__ . '/config.php';

/**
 * 首次部署守卫
 *
 * config.php 不在版本库内（内含数据库口令），所以刚解压的部署包一定缺它。
 * 若直接 require 会抛致命错误，异常处理器还没注册，PHP 默认把绝对路径
 * 和堆栈回显给访客。这里改成先给一句人话提示，再以 503 退出。
 */
if (!is_file($configFile)) {
    ini_set('display_errors', '0');
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    header('Retry-After: 300');
    echo "Museve 尚未完成部署：缺少 includes/config.php\n\n";
    echo "请执行：\n";
    echo "  cp includes/config.example.php includes/config.php\n\n";
    echo "然后填写其中的数据库连接信息（主机 / 库名 / 账号 / 口令），\n";
    echo "并确认 uploads/ 目录对 PHP 进程可写。\n";
    exit;
}

$config = require $configFile;

/**
 * 调试开关（config.php 未声明时视为关闭）
 *
 * 生产环境必须保持关闭：display_errors=On 会把异常栈、SQL 片段与文件路径
 * 直接回显给访问者。错误一律通过 error_log 落地。
 */
if (empty($config['debug'])) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// 富文本服务端净化（白名单）：文章正文等富文本入库前的唯一可信防线
require_once __DIR__ . '/html-sanitizer.php';

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

    // 设置表
    'settings' => "
        CREATE TABLE IF NOT EXISTS `settings` (
            `key` VARCHAR(100) PRIMARY KEY,
            `value` TEXT NOT NULL
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

    // 登录失败记录（用于限速，非业务数据）
    'login_attempts' => "
        CREATE TABLE IF NOT EXISTS `login_attempts` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `ip` VARCHAR(45) NOT NULL,
            `account` VARCHAR(50) NOT NULL,
            `attempted_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_ip_time` (`ip`, `attempted_at`),
            INDEX `idx_account_time` (`account`, `attempted_at`),
            INDEX `idx_attempted_at` (`attempted_at`)
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
    'police_icp' => '',
    'about_text' => '',
    'cache_version' => '1',
];

// ============================================================
// 3. 统一错误码常量
// ============================================================

/** 认证凭证错误 */
const ERR_INVALID_CREDENTIALS = 2000;
/** 账号被锁定 */
const ERR_ACCOUNT_LOCKED      = 2001;
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
// 4.1 站点设置读取（单次请求内缓存）
// ============================================================

/**
 * 读取全部站点设置
 *
 * 每次请求只查一次库，后续调用命中静态缓存。页面里凡是需要 settings
 * 的地方都应走这里，不要再各自 query 一遍。
 *
 * @return array<string, string>
 */
function getSettings(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $cache = [];
    try {
        $stmt = getDB()->query("SELECT `key`, `value` FROM `settings`");
        foreach ($stmt as $row) {
            $cache[(string)$row['key']] = (string)$row['value'];
        }
    } catch (Throwable $e) {
        // 建表失败等极端情况：退回内置默认值，保证页面可渲染
        error_log('[Museve] load settings failed: ' . $e->getMessage());
    }
    return $cache;
}

/**
 * 读取单个设置项
 *
 * 空字符串视为未设置，回退到 $default，再回退到 DEFAULT_SETTINGS 里的值。
 *
 * @param  string      $key     设置键名
 * @param  string|null $default 显式默认值
 * @return string|null
 */
function getSetting(string $key, ?string $default = null): ?string {
    $all = getSettings();
    if (isset($all[$key]) && $all[$key] !== '') {
        return $all[$key];
    }
    if ($default !== null) return $default;
    return DEFAULT_SETTINGS[$key] ?? null;
}

/**
 * 生成静态资源地址
 *
 * 规则：
 *   - 仅对站内静态资源（以 /resources/ 开头）应用 cdn_prefix，
 *     上传目录 /uploads/ 属于用户内容，不走 CDN，避免换 CDN 后图片全失效；
 *   - 自动附加 cache_version，改设置即刷新浏览器缓存。
 *
 * @param  string $path 以 / 开头的站内路径
 * @return string
 */
function assetUrl(string $path): string {
    $url = $path;

    if (str_starts_with($path, '/resources/')) {
        $cdn = getSetting('cdn_prefix', '');
        if ($cdn !== '') {
            $url = rtrim($cdn, '/') . $path;
        }
    }

    $ver = getSetting('cache_version', '1');
    if ($ver !== '' && $ver !== null) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . urlencode($ver);
    }
    return $url;
}

// ============================================================
// 4.2 口令哈希（统一入口）
// ============================================================

/**
 * 读取 bcrypt 强度
 *
 * 来自 config.php 的 security.bcrypt_cost，做 10~14 的夹取，
 * 防止改配置时把强度调到 4（可秒破）或 31（登录直接超时）。
 *
 * @return int
 */
function bcryptCost(): int {
    global $config;
    $cost = (int)($config['security']['bcrypt_cost'] ?? 12);
    return max(10, min(14, $cost));
}

/**
 * 生成口令哈希
 *
 * @param  string $password 明文口令
 * @return string bcrypt 哈希
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => bcryptCost()]);
}

/**
 * 口令强度下限（创建/重置用户时统一校验）
 *
 * @param  string $password 明文口令
 * @return string|null 不满足时返回提示文案，满足返回 null
 */
function validatePasswordStrength(string $password): ?string {
    if (mb_strlen($password) < 8) {
        return '密码至少 8 位';
    }
    if (mb_strlen($password) > 72) {
        // bcrypt 只取前 72 字节，超长会静默截断，直接拒绝更诚实
        return '密码不能超过 72 位';
    }
    return null;
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
    static $done = false;
    if ($done) return;
    $done = true;

    // 先探测已有表，只对缺失的表执行 DDL。
    // 以前是无条件跑全量 CREATE TABLE IF NOT EXISTS —— 每次请求 11 条 DDL，
    // 对 MySQL 是实打实的元数据锁开销。现在正常请求只剩一条 SHOW TABLES。
    $missing = array_keys(TABLE_DEFINITIONS);
    try {
        $existing = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $existing = array_map('strval', $existing ?: []);
        $missing  = array_values(array_diff(array_keys(TABLE_DEFINITIONS), $existing));
    } catch (Throwable $e) {
        // 列举失败（权限受限等）时保守地全量建表
        error_log('[Museve] SHOW TABLES failed: ' . $e->getMessage());
    }

    foreach ($missing as $name) {
        $db->exec(TABLE_DEFINITIONS[$name]);
    }

    // 默认数据只在缺表（首次安装 / 版本升级新增表）或设置表为空时写入
    $needSeed = $missing !== [];
    if (!$needSeed) {
        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM `settings`");
        $needSeed = ((int)$stmt->fetchColumn()) === 0;
    }

    if ($needSeed) {
        // 插入默认设置
        $insertStmt = $db->prepare("INSERT IGNORE INTO `settings` (`key`, `value`) VALUES (?, ?)");
        foreach (DEFAULT_SETTINGS as $key => $value) {
            $insertStmt->execute([$key, $value]);
        }

        // 创建默认管理员 (admin / admin123)
        $adminPassword = hashPassword('admin123');
        $adminStmt = $db->prepare("INSERT IGNORE INTO `users` (`account`, `username`, `password`, `role`) VALUES (?, ?, ?, ?)");
        $adminStmt->execute(['admin', '管理员', $adminPassword, 'admin']);
    }

    // 上传目录与建表无关，幂等且很便宜，保持每次检查
    $dirs = [__DIR__ . '/../uploads/memories', __DIR__ . '/../uploads/avatars'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
}

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

// ============================================================
// 9. 请求环境 / 安全响应头
// ============================================================

/**
 * 判断当前请求是否为 HTTPS
 *
 * 兼容反向代理：宝塔 / Nginx 前置时 PHP 侧看到的是 HTTP，
 * 需要读 X-Forwarded-Proto 才能正确给出 Secure Cookie。
 *
 * @return bool
 */
function isHttps(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    $proto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($proto === 'https') return true;

    return !empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower((string)$_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off';
}

/**
 * 判断当前请求是否走 API 通道（决定异常输出 JSON 还是 HTML）
 *
 * @return bool
 */
function isApiRequest(): bool {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return str_starts_with($path, '/api/') || str_starts_with($path, '/admin/api/');
}

/**
 * 输出统一的安全响应头
 *
 * 说明：模板里存在大量内联事件（onclick）与内联 <script>，
 * 因此 script-src 必须保留 'unsafe-inline'，CSP 在这里是纵深防御而非主力。
 * 真正的 XSS 防线是服务端的 HTML 白名单净化（includes/html-sanitizer.php）。
 *
 * @return void
 */
function sendSecurityHeaders(): void {
    if (headers_sent()) return;

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    $scriptSrc = "'self' 'unsafe-inline'";
    $styleSrc  = "'self' 'unsafe-inline'";
    $fontSrc   = "'self' data: https://fonts.gstatic.com";
    $imgSrc    = "'self' data: blob:";
    $connect   = "'self'";
    // TinyMCE 的编辑区是同源 iframe（about:blank 继承父页 origin，由 'self' 覆盖）；
    // 额外放行 blob:/data: 以防图片预览类场景被拦。
    $frame     = "'self' blob: data:";

    // 字体与图标样式仍走 Google Fonts（唯一保留的第三方请求）
    $styleSrc .= ' https://fonts.googleapis.com';

    // 若后台配置了 CDN 前缀，把它加进静态资源白名单，否则 CDN 化后样式/脚本会被拦
    $cdnPrefix = (string)getSetting('cdn_prefix', '');
    if ($cdnPrefix !== '' && preg_match('#^(https?://[^/]+)#i', $cdnPrefix, $m)) {
        $cdnOrigin = $m[1];
        $scriptSrc .= ' ' . $cdnOrigin;
        $styleSrc  .= ' ' . $cdnOrigin;
        $imgSrc    .= ' ' . $cdnOrigin;
        $fontSrc   .= ' ' . $cdnOrigin;
    }

    header(
        "Content-Security-Policy: default-src 'self'; "
        . "base-uri 'self'; "
        . "object-src 'none'; "
        . "frame-ancestors 'self'; "
        . "form-action 'self'; "
        . "script-src {$scriptSrc}; "
        . "style-src {$styleSrc}; "
        . "img-src {$imgSrc}; "
        . "font-src {$fontSrc}; "
        . "connect-src {$connect}; "
        . "frame-src {$frame}"
    );

    if (isHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// ============================================================
// 10. 全局异常兜底
// ============================================================

/**
 * 未捕获异常的统一出口
 *
 * 目标：对外只暴露 "服务器内部错误"，不泄露 SQL、表结构与文件路径；
 * 完整信息写 error_log 供运维排查。
 *
 * @param  Throwable $e 异常
 * @return void
 */
function museveHandleException(Throwable $e): void {
    error_log(sprintf(
        '[Museve] Uncaught %s: %s in %s:%d',
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    ));

    if (!headers_sent()) {
        http_response_code(500);
    }

    if (isApiRequest()) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'code'    => 500,
            'message' => '服务器内部错误，请稍后重试',
            'data'    => null,
        ], JSON_UNESCAPED_UNICODE);
        return;
    }

    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>服务暂时不可用</title></head>'
        . '<body style="margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;'
        . 'background:#F9F7F4;color:#3E3640;font-family:system-ui,-apple-system,sans-serif">'
        . '<div style="text-align:center;padding:2rem">'
        . '<h1 style="font-size:1.5rem;margin:0 0 .75rem">服务暂时不可用</h1>'
        . '<p style="color:#8E827F;margin:0">请稍后刷新重试。</p>'
        . '</div></body></html>';
}

set_exception_handler('museveHandleException');

// ============================================================
// 11. 启动
// ============================================================

// 放在异常处理器注册之后执行：建表/迁移期间的异常也能被统一兜住
autoCreateTables(getDB());

// 全站统一安全头（含 CSP / HSTS）。此时表已就绪，读取 cdn_prefix 才安全。
sendSecurityHeaders();
