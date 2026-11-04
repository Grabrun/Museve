<?php
// 后台 API - 网站设置
require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/auth_helper.php';

$currentUser = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// 非 GET 请求验证 CSRF
if ($method !== 'GET') verifyCsrfToken();

// 仅管理员可修改设置
if ($method === 'PUT' && $currentUser['role'] !== 'admin') {
    jsonResponse(403, '无权限，仅管理员可修改设置');
}

switch ($method) {
    case 'GET':
        $stmt = $db->query("SELECT `key`, `value` FROM settings");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['key']] = $row['value'];
        }
        jsonResponse(200, 'success', $settings);
        break;

    case 'PUT':
        $data = getJsonBody();
        if (empty($data)) jsonResponse(400, '没有需要更新的数据');

        // 只允许写 DEFAULT_SETTINGS 里声明过的键。
        // 以前是来者不拒：一个被劫持的 admin session 能往 settings 塞任意键值，
        // 也会让前端出现"看起来能存但没人读"的僵尸配置。
        $allowedKeys = array_keys(DEFAULT_SETTINGS);

        $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $saved = 0;

        foreach ($data as $key => $value) {
            if (!is_string($key) || !in_array($key, $allowedKeys, true)) {
                continue;
            }
            if (!is_scalar($value)) {
                continue;
            }

            $normalized = museveNormalizeSetting($key, (string)$value);
            if ($normalized === null) {
                continue;
            }

            $stmt->execute([$key, $normalized]);
            $saved++;
        }

        if ($saved === 0) {
            jsonResponse(400, '没有可保存的设置项');
        }

        // 更新缓存版本号，强制静态资源刷新（任何设置变更都可能影响页面输出）
        $stmt->execute(['cache_version', (string)time()]);

        jsonResponse(200, '设置保存成功', ['saved' => $saved]);
        break;

    default:
        jsonResponse(405, '方法不允许');
}

/**
 * 设置值归一化
 *
 * 把明显非法的输入挡在入库前，避免"改完设置整站白屏"：
 * 数值型夹取范围，枚举型限定取值，URL 型限定协议。
 *
 * @param  string $key   键名
 * @param  string $value 原始值
 * @return string|null 归一化后的值，null 表示丢弃该键
 */
function museveNormalizeSetting(string $key, string $value): ?string {
    $value = trim($value);

    switch ($key) {
        case 'upload_max_size':
            return (string)max(1, min(100, (int)$value));

        case 'login_token_expiry':
            return (string)max(5, min(1440, (int)$value));

        case 'maintenance_mode':
            return $value === '1' ? '1' : '0';

        case 'cdn_prefix':
            $value = rtrim($value, '/');
            if ($value === '') return '';
            // 只接受 http(s)，避免 javascript: / data: 之类被写进资源前缀
            return preg_match('#^https?://[^\s]+$#i', $value) === 1 ? $value : '';

        case 'upload_allowed_types':
            $parts = array_filter(array_map('trim', explode(',', $value)), fn($v) => $v !== '');
            $parts = array_values(array_intersect($parts, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']));
            return $parts ? implode(',', $parts) : 'image/jpeg,image/png,image/gif,image/webp';

        default:
            // 其余自由文本：限长，防止把 LONGTEXT 当仓库用
            return mb_substr($value, 0, 5000);
    }
}
