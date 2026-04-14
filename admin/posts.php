<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
meloverse_require_admin();
$pdo = meloverse_pdo();

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $pid = (int) ($_POST['post_id'] ?? 0);
    if ($pid > 0) {
        $s = $pdo->prepare('SELECT user_id, audio_path FROM audio_posts WHERE id = ? LIMIT 1');
        $s->execute([$pid]);
        $row = $s->fetch();
        if ($row) {
            $uid = (int) $row['user_id'];
            meloverse_delete_file_if_exists((string) $row['audio_path']);
            $pdo->prepare('DELETE FROM audio_posts WHERE id = ?')->execute([$pid]);
            $pdo->prepare('UPDATE users SET posts_count = GREATEST(posts_count - 1, 0) WHERE id = ?')->execute([$uid]);
        }
    }
    header('Location: posts.php');
    exit;
}

$rows = $pdo->query(
    'SELECT p.id, p.title, p.created_at, p.audio_path, u.username
     FROM audio_posts p
     INNER JOIN users u ON u.id = p.user_id
     ORDER BY p.id DESC LIMIT 200'
)->fetchAll() ?: [];

$pageTitle = 'Admin · Posts';
$currentUser = meloverse_current_user();
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="mv-wide">
    <h1>Audio posts</h1>
    <p><a href="admin/index.php">← Admin home</a></p>
    <div class="mv-table-wrap mv-hover-glow">
        <table class="mv-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>User</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int) $r['id'] ?></td>
                        <td><a href="post.php?id=<?= (int) $r['id'] ?>"><?= meloverse_h((string) $r['title']) ?></a></td>
                        <td>@<?= meloverse_h((string) $r['username']) ?></td>
                        <td class="mv-muted"><?= meloverse_h((string) $r['created_at']) ?></td>
                        <td>
                            <form method="post" class="mv-inline" onsubmit="return confirm('Delete this post and file?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="post_id" value="<?= (int) $r['id'] ?>">
                                <button type="submit" class="mv-btn mv-btn--sm mv-btn--danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
