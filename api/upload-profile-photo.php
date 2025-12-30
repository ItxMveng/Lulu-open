<?php
session_start();
require_once '../config/config.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

global $database;
$userId = $_SESSION['user_id'];

try {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors du téléchargement de la photo');
    }

    $file = $_FILES['photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Format d\'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('L\'image est trop volumineuse (max 5MB)');
    }

    $uploadDir = '../uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('profile_') . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erreur lors de la sauvegarde de la photo');
    }

    $oldPhoto = $database->fetch("SELECT photo_profil FROM utilisateurs WHERE id = ?", [$userId]);
    if ($oldPhoto && !empty($oldPhoto['photo_profil'])) {
        $oldPath = $uploadDir . $oldPhoto['photo_profil'];
        if (file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    $database->update(
        'utilisateurs',
        ['photo_profil' => $filename, 'updated_at' => date('Y-m-d H:i:s')],
        'id = ?',
        [$userId]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Photo de profil mise à jour avec succès',
        'photo_url' => '/lulu/uploads/profiles/' . $filename
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
