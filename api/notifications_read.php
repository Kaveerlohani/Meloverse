<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';

meloverse_require_method('POST');
$u = meloverse_require_login_json();
$uid = (int) $u['id'];
$pdo = meloverse_pdo();

$raw = file_get_contents('php://input') ?: '';
$json = json_decode($raw, true) ?: [];
$all = !empty($json['all']) || !empty($_POST['all']);
$id = (int) ($json['id'] ?? $_POST['id'] ?? 0);

if ($all) {
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$uid]);
} elseif ($id > 0) {
    $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
}
meloverse_json_response(['ok' => true]);
