<?php
/**
 * 文章 API (公开)
 * 获取文章列表/详情，含字数统计和上下篇导航
 */
require_once __DIR__ . '/../includes/connect.php';

$method = getMethod();

if ($method === 'GET') {
    try {
        $db = getDB();

        // 单篇文章详情
        $id = getRouteId();
        if ($id) {
            $stmt = $db->prepare("
                SELECT a.id, a.title, a.content, a.cover, a.created_at, a.updated_at,
                       u.username AS author_name, u.avatar AS author_avatar
                FROM articles a
                LEFT JOIN users u ON a.author_id = u.id
                WHERE a.id = :id AND a.status = 'published'
            ");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $article = $stmt->fetch();

            if (!$article) jsonResponse(3001, '文章不存在');

            // 字数和阅读时间
            $article['word_count'] = mb_strlen(strip_tags($article['content']));
            $article['read_time'] = max(1, ceil($article['word_count'] / 300));

            // 上一篇/下一篇
            $prevStmt = $db->prepare("SELECT id, title FROM articles WHERE status = 'published' AND created_at < :created ORDER BY created_at DESC LIMIT 1");
            $prevStmt->execute([':created' => $article['created_at']]);
            $article['prev'] = $prevStmt->fetch() ?: null;

            $nextStmt = $db->prepare("SELECT id, title FROM articles WHERE status = 'published' AND created_at > :created ORDER BY created_at ASC LIMIT 1");
            $nextStmt->execute([':created' => $article['created_at']]);
            $article['next'] = $nextStmt->fetch() ?: null;

            jsonSuccess($article);
        }

        // 文章列表
        [$page, $per, $offset] = getPagination();

        $countStmt = $db->query("SELECT COUNT(*) FROM articles WHERE status = 'published'");
        $total = (int)$countStmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT a.id, a.title, a.cover, a.created_at,
                   u.username AS author_name, u.avatar AS author_avatar,
                   CHAR_LENGTH(a.content) AS word_count
            FROM articles a
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.status = 'published'
            ORDER BY a.created_at DESC
            LIMIT :per OFFSET :offset
        ");
        $stmt->bindValue(':per', $per, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $list = $stmt->fetchAll();

        // 添加阅读时间
        foreach ($list as &$item) {
            $item['read_time'] = max(1, ceil(($item['word_count'] ?? 0) / 300));
        }

        jsonSuccess(['list' => $list, 'total' => $total, 'page' => $page, 'per' => $per]);
    } catch (PDOException $e) {
        jsonResponse(500, '服务器错误，请稍后重试');
    }
}

jsonResponse(405, '方法不允许');
