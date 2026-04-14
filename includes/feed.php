<?php
declare(strict_types=1);

/**
 * @return list<array<string,mixed>>
 */
function meloverse_fetch_posts(string $mode, int $viewerId, int $limit = 40): array
{
    $pdo = meloverse_pdo();
    $viewerId = max(0, $viewerId);

    $base = 'SELECT p.id, p.user_id, p.title, p.description, p.audio_path, p.audio_filename, p.mime_type,
        p.duration_seconds, p.plays_count, p.created_at,
        u.username, u.display_name, u.avatar_path,
        (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count,
        (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count';

    if ($viewerId > 0) {
        $base .= ', EXISTS(SELECT 1 FROM likes l WHERE l.post_id = p.id AND l.user_id = ' . (int) $viewerId . ') AS liked_by_me';
        $base .= ', EXISTS(SELECT 1 FROM bookmarks b WHERE b.post_id = p.id AND b.user_id = ' . (int) $viewerId . ') AS bookmarked';
    } else {
        $base .= ', 0 AS liked_by_me, 0 AS bookmarked';
    }

    $base .= ' FROM audio_posts p
        INNER JOIN users u ON u.id = p.user_id AND u.is_banned = 0';

    if ($mode === 'trending') {
        $base .= ' ORDER BY (
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY))
            + p.plays_count * 0.25
        ) DESC, p.created_at DESC';
    } else {
        $base .= ' ORDER BY p.created_at DESC';
    }

    $base .= ' LIMIT ' . (int) max(1, min(100, $limit));

    return $pdo->query($base)->fetchAll() ?: [];
}

/**
 * @return list<string>
 */
function meloverse_post_hashtags(int $postId): array
{
    $stmt = meloverse_pdo()->prepare(
        'SELECT h.tag FROM hashtags h
         INNER JOIN post_hashtags ph ON ph.hashtag_id = h.id
         WHERE ph.post_id = ? ORDER BY h.tag ASC'
    );
    $stmt->execute([$postId]);
    return array_column($stmt->fetchAll(), 'tag');
}
