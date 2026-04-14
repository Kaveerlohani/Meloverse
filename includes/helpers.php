<?php
declare(strict_types=1);

function meloverse_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $name = $GLOBALS['meloverse_config']['app']['session_name'] ?? 'MELOVERSESESSID';
    session_name((string) $name);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

function meloverse_base_url(): string
{
    $cfg = $GLOBALS['meloverse_config']['app']['base_url'] ?? '';
    if (is_string($cfg) && $cfg !== '') {
        return rtrim($cfg, '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $docRoot = @realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: '';
    $projRoot = @realpath(dirname(__DIR__)) ?: '';
    $webPath = '';
    if ($docRoot !== '' && $projRoot !== '' && str_starts_with(strtolower($projRoot), strtolower($docRoot))) {
        $rel = substr($projRoot, strlen($docRoot));
        $rel = str_replace('\\', '/', $rel);
        $rel = '/' . trim($rel, '/');
        if ($rel !== '/') {
            $parts = array_map('rawurlencode', explode('/', trim($rel, '/')));
            $webPath = '/' . implode('/', $parts);
        }
    }
    if ($webPath === '') {
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir = dirname($script);
        if (str_ends_with($dir, '/admin')) {
            $dir = dirname($dir);
        }
        $webPath = ($dir === '/' || $dir === '.') ? '' : $dir;
    }

    return rtrim($scheme . '://' . $host . $webPath, '/');
}

function meloverse_h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function meloverse_is_mobile_ua(): bool
{
    $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '') {
        return false;
    }
    return (bool) preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i', $ua);
}

/** Guest browsing is desktop-only; mobile visitors are sent to log in. */
function meloverse_guest_mobile_redirect(): void
{
    if (meloverse_current_user()) {
        return;
    }
    if (!meloverse_is_mobile_ua()) {
        return;
    }
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if (in_array($script, ['login.php', 'register.php', 'install.php'], true)) {
        return;
    }
    $next = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: login.php?next=' . rawurlencode($next) . '&guest=mobile');
    exit;
}

function meloverse_json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function meloverse_require_method(string $method): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== strtoupper($method)) {
        meloverse_json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
    }
}

function meloverse_safe_redirect_path(string $next): string
{
    $next = trim($next);
    if ($next === '' || str_contains($next, "\n") || str_contains($next, "\r")) {
        return 'index.php';
    }
    if (preg_match('#^https?://#i', $next)) {
        return 'index.php';
    }
    if (!preg_match('#^[a-zA-Z0-9_./?=&%\-#]+$#', $next)) {
        return 'index.php';
    }
    return $next;
}

function meloverse_parse_hashtags(string $text): array
{
    preg_match_all('/#([\p{L}\p{N}_]{2,100})/u', $text, $m);
    $tags = array_unique(array_map('mb_strtolower', $m[1] ?? []));
    return array_values($tags);
}

function meloverse_public_storage_url(string $relativePath): string
{
    $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
    $prefix = (string) ($GLOBALS['meloverse_config']['storage']['public_base_url'] ?? '');
    if ($prefix !== '') {
        return rtrim($prefix, '/') . '/' . $relativePath;
    }
    return meloverse_base_url() . '/' . $relativePath;
}
