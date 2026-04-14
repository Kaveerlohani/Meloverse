<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/bootstrap.php';
meloverse_require_admin();
$pdo = meloverse_pdo();
$users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$posts = (int) $pdo->query('SELECT COUNT(*) FROM audio_posts')->fetchColumn();
$pageTitle = 'Admin';
$currentUser = meloverse_current_user();
require dirname(__DIR__) . '/partials/header.php';
?>
<div class="mv-narrow">
    <h1>Admin</h1>
    <p class="mv-muted">Signed in as <?= meloverse_h((string) $currentUser['email']) ?>.</p>
    <div class="mv-admin-cards">
        <a class="mv-admin-card mv-hover-glow" href="admin/users.php">
            <strong><?= $users ?></strong>
            <span>Users</span>
        </a>
        <a class="mv-admin-card mv-hover-glow" href="admin/posts.php">
            <strong><?= $posts ?></strong>
            <span>Audio posts</span>
        </a>
    </div>
</div>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
