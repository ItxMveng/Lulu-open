<?php
session_start();
require_once '../config/config.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /lulu/login.php');
    exit;
}

global $database;
$userId = $_SESSION['user_id'];

try {
    if (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors du téléchargement du CV');
    }

    $file = $_FILES['cv_file'];
    $allowedTypes = ['application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Format de fichier non autorisé. Utilisez un PDF.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('Le fichier est trop volumineux (max 5MB)');
    }

    $uploadDir = '../uploads/cv/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('cv_') . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erreur lors de la sauvegarde du fichier');
    }

    // Mettre à jour la base de données
    $candidat = $database->fetch("SELECT id FROM cvs WHERE utilisateur_id = ?", [$userId]);

    if ($candidat) {
        $database->update(
            'cvs',
            ['cv_fichier' => $filename, 'updated_at' => date('Y-m-d H:i:s')],
            'utilisateur_id = ?',
            [$userId]
        );
    } else {
        $database->insert('cvs', [            'utilisateur_id' => $userId,
            'cv_fichier' => $filename,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    flashMessage('CV téléchargé avec succès', 'success');
    header('Location: /lulu/views/prestataire_candidat/dashboard.php');
    exit;

} catch (Exception $e) {
    flashMessage('Erreur: ' . $e->getMessage(), 'error');
    header('Location: /lulu/views/prestataire_candidat/dashboard.php');
    exit;
}
?>
