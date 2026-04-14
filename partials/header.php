<?php
declare(strict_types=1);
/** @var string $pageTitle */
/** @var ?array $currentUser */
$cu = $currentUser ?? meloverse_current_user();
$title = $pageTitle ?? 'MELOVERSE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= meloverse_h($title) ?> · MELOVERSE</title>
    <base href="<?= meloverse_h(meloverse_base_url()) ?>/">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="mv-body" data-base="<?= meloverse_h(meloverse_base_url()) ?>" data-logged-in="<?= $cu ? '1' : '0' ?>" data-mobile="<?= meloverse_is_mobile_ua() ? '1' : '0' ?>">
<header class="mv-topbar">
    <a class="mv-logo" href="index.php">MELOVERSE</a>
    <nav class="mv-nav">
        <a href="index.php">Home</a>
        <a href="search.php">Search</a>
        <?php if ($cu): ?>
            <a href="upload.php">Upload</a>
            <a href="bookmarks.php">Saved</a>
            <a href="notifications.php" class="mv-notif-link">Notifications<?php
                $pdo = meloverse_pdo();
                $c = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
                $c->execute([(int) $cu['id']]);
                $n = (int) $c->fetchColumn();
                if ($n > 0) {
                    echo ' <span class="mv-badge">' . $n . '</span>';
                }
            ?></a>
            <a href="profile.php?u=<?= meloverse_h($cu['username']) ?>">Profile</a>
            <?php if (($cu['role'] ?? '') === 'admin'): ?>
                <a href="admin/index.php">Admin</a>
            <?php endif; ?>
            <a href="logout.php">Log out</a>
        <?php else: ?>
            <a href="login.php">Log in</a>
            <a href="register.php" class="mv-btn mv-btn--sm">Sign up</a>
        <?php endif; ?>
    </nav>
    <button type="button" class="mv-menu-toggle" aria-label="Menu" aria-expanded="false">☰</button>
</header>
<main class="mv-main">
