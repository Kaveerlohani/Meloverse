<?php
declare(strict_types=1);

use PDO;

function meloverse_notify(int $recipientId, int $actorId, string $type, ?int $postId = null): void
{
    if ($recipientId === $actorId) {
        return;
    }
    if (!in_array($type, ['like', 'comment', 'follow', 'mention'], true)) {
        return;
    }
    $pdo = meloverse_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, actor_id, type, post_id) VALUES (?, ?, ?, ?)'
    );
    $stmt->execute([$recipientId, $actorId, $type, $postId]);
}
