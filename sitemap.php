<?php
// 暮想 Museve - 动态 Sitemap
require_once __DIR__ . '/includes/connect.php';

header('Content-Type: application/xml; charset=utf-8');

$db = getDB();
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- 首页 -->
    <url>
        <loc><?= $baseUrl ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>

    <!-- 回忆 -->
    <url>
        <loc><?= $baseUrl ?>/memories</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- 悄悄话 -->
    <url>
        <loc><?= $baseUrl ?>/whispers</loc>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>

    <!-- 文章列表 -->
    <url>
        <loc><?= $baseUrl ?>/articles</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>

    <!-- 关于 -->
    <url>
        <loc><?= $baseUrl ?>/about</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- 文章详情 -->
    <?php
    $articles = $db->query("SELECT id, updated_at FROM articles WHERE status = 'published' ORDER BY id DESC")->fetchAll();
    foreach ($articles as $article): ?>
    <url>
        <loc><?= $baseUrl ?>/read/<?= $article['id'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($article['updated_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php endforeach; ?>
</urlset>
