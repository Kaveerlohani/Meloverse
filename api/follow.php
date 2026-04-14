<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/notify.php';

meloverse_require_method('POST');
$u = meloverse_require_login_json();

$raw = file_get_contents('php://input') ?: '';
$json = json_decode($raw, true);
$targetId = (int) (($json['user_id'] ?? $_POST['user_id'] ?? 0));
if ($targetId <= 0) {
    meloverse_json_response(['ok' => false, 'error' => 'Invalid user'], 400);
}
$uid = (int) $u['id'];
if ($targetId === $uid) {
    meloverse_json_response(['ok' => false, 'error' => 'Cannot follow yourself'], 400);
}

$pdo = meloverse_pdo();
$t = $pdo->prepare('SELECT id FROM users WHERE id = ? AND is_banned = 0 LIMIT 1');
$t->execute([$targetId]);
if (!$t->fetch()) {
    meloverse_json_response(['ok' => false, 'error' => 'User not found'], 404);
}

$ex = $pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
$ex->execute([$uid, $targetId]);
if ($ex->fetch()) {
    $pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?')->execute([$uid, $targetId]);
    $pdo->prepare('UPDATE users SET following_count = GREATEST(following_count - 1, 0) WHERE id = ?')->execute([$uid]);
    $pdo->prepare('UPDATE users SET followers_count = GREATEST(followers_count - 1, 0) WHERE id = ?')->execute([$targetId]);
    meloverse_json_response(['ok' => true, 'following' => false]);
}

$pdo->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)')->execute([$uid, $targetId]);
$pdo->prepare('UPDATE users SET following_count = following_count + 1 WHERE id = ?')->execute([$uid]);
$pdo->prepare('UPDATE users SET followers_count = followers_count + 1 WHERE id = ?')->execute([$targetId]);
meloverse_notify($targetId, $uid, 'follow', null);
meloverse_json_response(['ok' => true, 'following' => true]);
