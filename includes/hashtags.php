<?php
declare(strict_types=1);

function meloverse_sync_post_hashtags(PDO $pdo, int $postId, string $text): void
{
    $pdo->prepare('DELETE FROM post_hashtags WHERE post_id = ?')->execute([$postId]);
    $tags = meloverse_parse_hashtags($text);
    if (!$tags) {
        return;
    }
    $sel = $pdo->prepare('SELECT id FROM hashtags WHERE tag = ? LIMIT 1');
    $ins = $pdo->prepare('INSERT INTO hashtags (tag) VALUES (?)');
    $link = $pdo->prepare('INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)');
    foreach ($tags as $tag) {
        $sel->execute([$tag]);
        $hid = (int) $sel->fetchColumn();
        if ($hid <= 0) {
            $ins->execute([$tag]);
            $hid = (int) $pdo->lastInsertId();
        }
        if ($hid > 0) {
            $link->execute([$postId, $hid]);
        }
    }
}
