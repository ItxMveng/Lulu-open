<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: /lulu/login.php');
    exit;
}

global $database;
$userId = $_SESSION['user_id'];

try {
    // Récupérer le profil prestataire
    $prestataire = $database->fetch("SELECT id FROM profils_prestataires WHERE utilisateur_id = ?", [$userId]);

    if (!$prestataire) {
        throw new Exception('Profil prestataire non trouvé');
    }

    // Gérer l'upload de l'image
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Erreur lors du téléchargement de l\'image');
    }

    $file = $_FILES['image'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024; // 2MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Format d\'image non autorisé. Utilisez JPG ou PNG.');
    }

    if ($file['size'] > $maxSize) {
        throw new Exception('L\'image est trop volumineuse (max 2MB)');
    }

    // Créer le dossier si nécessaire
    $uploadDir = '../uploads/portfolios/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('portfolio_') . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Erreur lors de l\'enregistrement de l\'image');
    }

    // Enregistrer dans la base de données
    $database->insert('portfolios', [
        'prestataire_id' => $prestataire['id'],
        'titre' => $_POST['titre'] ?? '',
        'description' => $_POST['description'] ?? '',
        'image' => $filename,
        'lien' => $_POST['lien'] ?? null
    ]);

    flashMessage('Réalisation ajoutée avec succès', 'success');
    header('Location: /lulu/views/prestataire_candidat/dashboard.php');
    exit;

} catch (Exception $e) {
    flashMessage('Erreur: ' . $e->getMessage(), 'error');
    header('Location: /lulu/views/prestataire_candidat/dashboard.php');
    exit;
}
?>
