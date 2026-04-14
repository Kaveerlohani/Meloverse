<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
meloverse_require_admin();
$pdo = meloverse_pdo();

if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $uid = (int) ($_POST['user_id'] ?? 0);
    $ban = (int) ($_POST['ban'] ?? 0) === 1 ? 1 : 0;
    if ($uid > 0) {
        $me = (int) meloverse_current_user()['id'];
        if ($uid !== $me) {
            $pdo->prepare('UPDATE users SET is_banned = ? WHERE id = ?')->execute([$ban, $uid]);
        }
    }
    header('Location: users.php');
    exit;
}

$rows = $pdo->query(
    'SELECT id, email, username, display_name, role, is_banned, created_at, posts_count
     FROM users ORDER BY id DESC LIMIT 200'
)->fetchAll() ?: [];

$pageTitle = 'Admin · Users';
$currentUser = meloverse_current_user();
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="mv-wide">
    <h1>Users</h1>
    <p><a href="admin/index.php">← Admin home</a></p>
    <div class="mv-table-wrap mv-hover-glow">
        <table class="mv-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Posts</th>
                    <th>Banned</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int) $r['id'] ?></td>
                        <td><?= meloverse_h((string) $r['email']) ?></td>
                        <td><a href="profile.php?u=<?= meloverse_h((string) $r['username']) ?>">@<?= meloverse_h((string) $r['username']) ?></a></td>
                        <td><?= meloverse_h((string) $r['role']) ?></td>
                        <td><?= (int) $r['posts_count'] ?></td>
                        <td><?= (int) $r['is_banned'] ? 'yes' : 'no' ?></td>
                        <td>
                            <?php if ((int) $r['id'] !== (int) $currentUser['id']): ?>
                                <form method="post" class="mv-inline">
                                    <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
                                    <?php if ((int) $r['is_banned'] === 0): ?>
                                        <input type="hidden" name="ban" value="1">
                                        <button type="submit" class="mv-btn mv-btn--sm mv-btn--danger">Ban</button>
                                    <?php else: ?>
                                        <input type="hidden" name="ban" value="0">
                                        <button type="submit" class="mv-btn mv-btn--sm mv-btn--secondary">Unban</button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <span class="mv-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
