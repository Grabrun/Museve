<?php
// 暮想 Museve 配置文件 (示例)
// 复制此文件为 config.php 并修改以下配置

return [
    // 调试模式：生产环境必须为 false
    // 开启后错误会带堆栈回显给访问者，只在本地排查时使用
    'debug' => false,

    // 数据库配置
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'museve',
        'username' => 'root',
        'password' => '',          // 修改为你的数据库密码
        'charset' => 'utf8mb4',
    ],

    // 站点配置
    'site' => [
        'title' => '暮想',
        'subtitle' => '在薄暮时分，温柔地想起。',
        'logo' => '/resources/images/logo.svg',
        'favicon' => '/resources/images/favicon.png',
        'default_avatar' => '/resources/images/default-avatar.png',
        // 注意：CDN 前缀请在后台「网站设置 → 高级设置」里配置（存数据库、即时生效）。
        // 这里的 cdn 已废弃，保留仅为兼容旧配置。
        'cdn' => '',
    ],

    // 安全配置 —— 以下参数已真正接入代码，不再是摆设
    //    bcrypt_cost            → includes/connect.php 的 hashPassword()
    //    login_max_attempts     → admin/api/auth_helper.php 的登录锁定
    //    login_lockout_minutes  → 同上（锁定窗口与提示文案）
    //    cookie_*               → admin/api/auth_helper.php 的 authCookieOptions()
    'security' => [
        'bcrypt_cost' => 12,
        'login_max_attempts' => 5,
        'login_lockout_minutes' => 30,
        'cookie_httponly' => true,
        // 默认跟随真实协议：HTTPS 自动加 Secure。
        // 仅当 PHP 侧探测不到 HTTPS（特殊代理）时才需要在这里强制为 true。
        'cookie_secure' => false,
        'cookie_samesite' => 'Strict',
    ],

    // 上传配置
    // 上限与允许类型以后台「上传设置」为准（存数据库）；
    // 这里只保留上传目录这类部署期参数。
    'upload' => [
        'max_size' => 5 * 1024 * 1024,            // 旧版遗留，已不参与校验
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], // 同上
        'upload_dir' => __DIR__ . '/../uploads/',
    ],
];
