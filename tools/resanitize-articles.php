<?php
/**
 * 一次性清理：把已入库的文章正文重新过一遍服务端净化
 *
 * 背景：本次修复之前，articles.content 只经过 strip_tags($html, $allowedTags)，
 * 该方法不处理属性，`<img onerror=...>`、`<a href="javascript:...">` 会原样入库。
 * 修复后新写入的内容已安全，但历史数据需要跑一次本脚本彻底清掉。
 *
 * 用法（在服务器上执行）：
 *   php tools/resanitize-articles.php           # 预演，只统计不写库
 *   php tools/resanitize-articles.php --apply   # 实际写回
 *
 * 建议先备份：bash scripts/backup.sh
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('仅允许命令行执行');
}

require __DIR__ . '/../includes/connect.php';

$apply = in_array('--apply', $argv ?? [], true);

$db = getDB();
$rows = $db->query("SELECT id, title, content FROM articles ORDER BY id ASC")->fetchAll();

$total = count($rows);
$changed = 0;
$update = $db->prepare("UPDATE articles SET content = ?, updated_at = updated_at WHERE id = ?");

echo "共 {$total} 篇文章" . ($apply ? "，开始写回…" : "，当前为预演模式（加 --apply 才会写库）") . "\n";

foreach ($rows as $row) {
    $original = (string)$row['content'];
    $clean = sanitizeRichHtml($original);

    if ($clean === $original) {
        continue;
    }

    $changed++;
    echo sprintf(
        "  #%d %s  %d → %d 字节\n",
        (int)$row['id'],
        mb_substr((string)$row['title'], 0, 24),
        strlen($original),
        strlen($clean)
    );

    if ($apply) {
        // 注意：不改 updated_at（避免把"内容被清理"误显示成"文章被编辑"）
        $update->execute([$clean, (int)$row['id']]);
    }
}

echo "\n" . ($changed === 0
    ? "✓ 没有需要清理的内容\n"
    : ($apply
        ? "✓ 已清理 {$changed} 篇\n"
        : "⚠️  {$changed} 篇需要清理，确认后加 --apply 执行\n"));
