<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/notify.php';

meloverse_require_method('POST');
$u = meloverse_require_login_json();

$raw = file_get_contents('php://input') ?: '';
$json = json_decode($raw, true) ?: [];
$pid = (int) ($json['post_id'] ?? $_POST['post_id'] ?? 0);
$body = trim((string) ($json['body'] ?? $_POST['body'] ?? ''));
$parentId = isset($json['parent_id']) ? (int) $json['parent_id'] : (int) ($_POST['parent_id'] ?? 0);
$parentId = $parentId > 0 ? $parentId : null;

if ($pid <= 0 || $body === '' || mb_strlen($body) > 4000) {
    meloverse_json_response(['ok' => false, 'error' => 'Invalid comment'], 400);
}

$pdo = meloverse_pdo();
$chk = $pdo->prepare('SELECT id, user_id FROM audio_posts WHERE id = ? LIMIT 1');
$chk->execute([$pid]);
$post = $chk->fetch();
if (!$post) {
    meloverse_json_response(['ok' => false, 'error' => 'Post not found'], 404);
}

if ($parentId !== null) {
    $pc = $pdo->prepare('SELECT id FROM comments WHERE id = ? AND post_id = ? LIMIT 1');
    $pc->execute([$parentId, $pid]);
    if (!$pc->fetch()) {
        meloverse_json_response(['ok' => false, 'error' => 'Invalid reply target'], 400);
    }
}

$uid = (int) $u['id'];
$ins = $pdo->prepare(
    'INSERT INTO comments (post_id, user_id, parent_id, body) VALUES (?, ?, ?, ?)'
);
$ins->execute([$pid, $uid, $parentId, $body]);
$cid = (int) $pdo->lastInsertId();

$owner = (int) $post['user_id'];
meloverse_notify($owner, $uid, 'comment', $pid);

meloverse_json_response([
    'ok' => true,
    'comment' => [
        'id' => $cid,
        'body' => $body,
        'username' => $u['username'],
        'display_name' => $u['display_name'],
        'parent_id' => $parentId,
        'created_at' => date('c'),
    ],
]);
