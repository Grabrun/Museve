<?php
/**
 * 维护模式页面
 *
 * 由 index.php 在 maintenance_mode=1 时以 503 状态输出。
 * 刻意做成自包含的静态页面：不查询数据库、不依赖导航与外部资源，
 * 避免"维护期间连维护页都打不开"的尴尬。
 */

$siteTitle = getSetting('site_title', '暮想');
$subtitle  = '正在整理回忆，请稍后回来';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title><?= htmlspecialchars($siteTitle) ?> · 维护中</title>
    <style>
        :root {
            --bg: #F9F7F4;
            --ink: #3E3640;
            --muted: #8E827F;
            --rose: #DDB8B8;
            --rose-deep: #B28B8B;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            color: var(--ink);
            font-family: "Noto Serif SC", "Source Han Serif SC", Georgia, serif;
            padding: 2rem;
        }
        .wrap { text-align: center; max-width: 26rem; }
        .dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--rose);
            margin: 0 auto 1.75rem;
            box-shadow: 0 0 0 0 rgba(221, 184, 184, .7);
            animation: pulse 2.4s ease-out infinite;
        }
        @keyframes pulse {
            0%   { box-shadow: 0 0 0 0 rgba(221, 184, 184, .7); }
            70%  { box-shadow: 0 0 0 18px rgba(221, 184, 184, 0); }
            100% { box-shadow: 0 0 0 0 rgba(221, 184, 184, 0); }
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: .05em;
            margin: 0 0 .875rem;
        }
        p { margin: 0; color: var(--muted); font-size: .9375rem; line-height: 1.9; }
        .brand {
            display: inline-block;
            margin-top: 2.25rem;
            font-size: .75rem;
            letter-spacing: .28em;
            color: var(--rose-deep);
            text-transform: uppercase;
        }
        @media (prefers-reduced-motion: reduce) {
            .dot { animation: none; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="dot" aria-hidden="true"></div>
        <h1><?= htmlspecialchars($siteTitle) ?></h1>
        <p><?= htmlspecialchars($subtitle) ?></p>
        <span class="brand">Museve</span>
    </div>
</body>
</html>
