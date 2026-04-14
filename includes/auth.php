<?php
declare(strict_types=1);

function meloverse_current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $id = (int) $_SESSION['user_id'];
    if ($id <= 0) {
        return null;
    }
    $stmt = meloverse_pdo()->prepare(
        'SELECT id, email, username, display_name, bio, avatar_path, role, is_banned, created_at
         FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        unset($_SESSION['user_id']);
        return null;
    }
    if ((int) $row['is_banned'] === 1) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $row;
}

function meloverse_require_login_json(): array
{
    $u = meloverse_current_user();
    if (!$u) {
        meloverse_json_response(['ok' => false, 'error' => 'Authentication required'], 401);
    }
    return $u;
}

function meloverse_require_login_page(): array
{
    $u = meloverse_current_user();
    if (!$u) {
        header('Location: login.php?next=' . rawurlencode($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
    return $u;
}

function meloverse_require_admin(): array
{
    $u = meloverse_require_login_page();
    if (($u['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
    return $u;
}

function meloverse_login_user(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function meloverse_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
