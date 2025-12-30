<?php
require_once 'config/config.php';

$cvFile = $_GET['file'] ?? '';

if (empty($cvFile)) {
    header('HTTP/1.0 404 Not Found');
    exit('Fichier non spécifié');
}

// Sécurité : vérifier que le fichier ne contient pas de caractères dangereux
if (preg_match('/[^a-zA-Z0-9_\-\.]/', $cvFile)) {
    header('HTTP/1.0 403 Forbidden');
    exit('Nom de fichier invalide');
}

$filePath = __DIR__ . '/uploads/cv/' . $cvFile;

if (!file_exists($filePath)) {
    header('HTTP/1.0 404 Not Found');
    exit('Fichier non trouvé');
}

// Définir les headers pour afficher le PDF
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . basename($cvFile) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: public, max-age=3600');

// Lire et afficher le fichier
readfile($filePath);
exit;
