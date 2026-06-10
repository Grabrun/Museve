<?php
// 暮想 Museve 配置文件 (示例)
// 复制此文件为 config.php 并修改以下配置

return [
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
        'logo' => 'resources/images/logo.svg',
        'favicon' => 'resources/images/favicon.png',
        'default_avatar' => 'resources/images/default-avatar.png',
        'cdn' => '',               // CDN 前缀，如 https://cdn.example.com
    ],

    // 安全配置
    'security' => [
        'bcrypt_cost' => 12,
        'login_max_attempts' => 5,
        'login_lockout_minutes' => 30,
        'cookie_httponly' => true,
        'cookie_secure' => false,  // 生产环境改为 true (需 HTTPS)
        'cookie_samesite' => 'Strict',
    ],

    // 上传配置
    'upload' => [
        'max_size' => 5 * 1024 * 1024, // 5MB
        'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'upload_dir' => __DIR__ . '/../uploads/',
    ],
];
