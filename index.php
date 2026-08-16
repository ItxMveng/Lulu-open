<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once base_path('includes/ErrorHandler.php');

ErrorHandler::register();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 1; mode=block');
header("Content-Security-Policy: default-src 'self' https: data: 'unsafe-inline' 'unsafe-eval'; img-src 'self' https: data:; connect-src 'self' https:;");

require_once base_path('routes.php');

Router::dispatch();
