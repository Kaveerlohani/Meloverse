<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/notify.php';

meloverse_require_method('POST');
$u = meloverse_require_login_json();

$raw = file_get_contents('php://input') ?: '';
$json = json_decode($raw, true);
$pid = (int) (($json['post_id'] ?? $_POST['post_id'] ?? 0));
if ($pid <= 0) {
    meloverse_json_response(['ok' => false, 'error' => 'Invalid post'], 400);
}

$pdo = meloverse_pdo();
$chk = $pdo->prepare('SELECT id, user_id FROM audio_posts WHERE id = ? LIMIT 1');
$chk->execute([$pid]);
$post = $chk->fetch();
if (!$post) {
    meloverse_json_response(['ok' => false, 'error' => 'Not found'], 404);
}

$uid = (int) $u['id'];
$exists = $pdo->prepare('SELECT 1 FROM likes WHERE user_id = ? AND post_id = ?');
$exists->execute([$uid, $pid]);
if ($exists->fetch()) {
    $pdo->prepare('DELETE FROM likes WHERE user_id = ? AND post_id = ?')->execute([$uid, $pid]);
    $cnt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
    $cnt->execute([$pid]);
    meloverse_json_response(['ok' => true, 'liked' => false, 'likes_count' => (int) $cnt->fetchColumn()]);
}

$pdo->prepare('INSERT INTO likes (user_id, post_id) VALUES (?, ?)')->execute([$uid, $pid]);
$owner = (int) $post['user_id'];
meloverse_notify($owner, $uid, 'like', $pid);

$cnt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE post_id = ?');
$cnt->execute([$pid]);
meloverse_json_response(['ok' => true, 'liked' => true, 'likes_count' => (int) $cnt->fetchColumn()]);
