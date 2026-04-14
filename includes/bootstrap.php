<?php
declare(strict_types=1);

$configPath = dirname(__DIR__) . '/config.php';
if (!is_readable($configPath)) {
    header('Content-Type: text/plain; charset=utf-8', true, 500);
    echo "Missing config.php. Copy config.example.php to config.php and run install.php.";
    exit;
}

/** @var array<string,mixed> $GLOBALS['meloverse_config'] */
$GLOBALS['meloverse_config'] = require $configPath;

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/storage.php';
require_once __DIR__ . '/feed.php';
require_once __DIR__ . '/hashtags.php';

meloverse_session_start();
