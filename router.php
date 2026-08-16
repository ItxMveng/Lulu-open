<?php
declare(strict_types=1);

/**
 * Routeur pour le serveur web intégré de PHP (dev local + Render).
 *   php -S 0.0.0.0:8000 router.php
 *
 * Sert les fichiers statiques existants (assets, uploads) tels quels
 * et délègue tout le reste au front-controller index.php.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $path);

// Fichier statique réel (jamais un .php exécutable hors index) → laisser le serveur le servir.
if ($path !== '/' && is_file($file)) {
    $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
    if ($extension !== 'php') {
        return false;
    }
}

require __DIR__ . '/index.php';
