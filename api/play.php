<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

meloverse_require_method('POST');
$raw = file_get_contents('php://input') ?: '';
$json = json_decode($raw, true) ?: [];
$pid = (int) ($json['post_id'] ?? $_POST['post_id'] ?? 0);
if ($pid <= 0) {
    meloverse_json_response(['ok' => false, 'error' => 'Invalid'], 400);
}
$pdo = meloverse_pdo();
$stmt = $pdo->prepare('UPDATE audio_posts SET plays_count = plays_count + 1 WHERE id = ?');
$stmt->execute([$pid]);
meloverse_json_response(['ok' => true]);
