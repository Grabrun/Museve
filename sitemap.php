<?php
// 暮想 Museve - 动态 Sitemap
require_once __DIR__ . '/includes/connect.php';

header('Content-Type: application/xml; charset=utf-8');

$db = getDB();

// Host 头是不可信输入：直接拼进 XML 会形成反射注入。
// 只接受"主机名 + 可选端口"的形状，其余回退到 SERVER_NAME。
$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
if (!preg_match('/^[A-Za-z0-9.-]+(:\d{1,5})?$/', $host)) {
    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';
}

$baseUrl = (isHttps() ? 'https' : 'http') . '://' . $host;

/**
 * 输出一个 sitemap 地址节点
 *
 * @param string      $baseUrl  站点根
 * @param string      $path     路径
 * @param string      $freq     更新频率
 * @param string      $priority 权重
 * @param string|null $lastmod  最后修改日 Y-m-d
 */
function sitemapUrl(string $baseUrl, string $path, string $freq, string $priority, ?string $lastmod = null): void {
    $loc = htmlspecialchars($baseUrl . $path, ENT_QUOTES | ENT_XML1, 'UTF-8');
    echo "    <url>\n";
    echo "        <loc>{$loc}</loc>\n";
    if ($lastmod !== null) {
        echo "        <lastmod>{$lastmod}</lastmod>\n";
    }
    echo "        <changefreq>{$freq}</changefreq>\n";
    echo "        <priority>{$priority}</priority>\n";
    echo "    </url>\n";
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php
sitemapUrl($baseUrl, '/',         'daily',   '1.0');
sitemapUrl($baseUrl, '/memories', 'weekly',  '0.8');
sitemapUrl($baseUrl, '/whispers', 'weekly',  '0.7');
sitemapUrl($baseUrl, '/articles', 'daily',   '0.9');
sitemapUrl($baseUrl, '/about',    'monthly', '0.5');

$articles = $db->query("SELECT id, created_at, updated_at FROM articles WHERE status = 'published' ORDER BY id DESC")->fetchAll();
foreach ($articles as $article) {
    // updated_at 允许为 NULL（历史数据/直接导入的行）。
    // 直接 strtotime(null) 会得到 1970-01-01，且 PHP 8.1+ 还会抛 deprecation。
    $ts = strtotime((string)($article['updated_at'] ?: $article['created_at']));
    $lastmod = date('Y-m-d', $ts !== false ? $ts : time());

    sitemapUrl($baseUrl, '/read/' . (int)$article['id'], 'monthly', '0.7', $lastmod);
}
?>
</urlset>
