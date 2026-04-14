<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

meloverse_require_method('POST');
$u = meloverse_require_login_json();

$raw = file_get_contents('php://input') ?: '';
$json = json_decode($raw, true);
$pid = (int) (($json['post_id'] ?? $_POST['post_id'] ?? 0));
if ($pid <= 0) {
    meloverse_json_response(['ok' => false, 'error' => 'Invalid post'], 400);
}

$pdo = meloverse_pdo();
$chk = $pdo->prepare('SELECT id FROM audio_posts WHERE id = ? LIMIT 1');
$chk->execute([$pid]);
if (!$chk->fetch()) {
    meloverse_json_response(['ok' => false, 'error' => 'Not found'], 404);
}

$uid = (int) $u['id'];
$ex = $pdo->prepare('SELECT 1 FROM bookmarks WHERE user_id = ? AND post_id = ?');
$ex->execute([$uid, $pid]);
if ($ex->fetch()) {
    $pdo->prepare('DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?')->execute([$uid, $pid]);
    meloverse_json_response(['ok' => true, 'bookmarked' => false]);
}
$pdo->prepare('INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)')->execute([$uid, $pid]);
meloverse_json_response(['ok' => true, 'bookmarked' => true]);
