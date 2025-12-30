<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $userId = $_SESSION['user_id'];
    $profileType = $_POST['profile_type'] ?? null;

    if (!$profileType || !in_array($profileType, ['prestataire', 'candidat'])) {
        throw new Exception('Type de profil invalide');
    }

    $database = Database::getInstance();

    if ($profileType === 'prestataire') {
        $prestataire = $database->fetch(
            "SELECT id FROM profils_prestataires WHERE utilisateur_id = ?",
            [$userId]
        );

        if (!$prestataire) {
            throw new Exception('Profil prestataire non trouvé');
        }

        $database->delete(
            'profils_prestataires',
            'utilisateur_id = ?',
            [$userId]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Profil prestataire supprimé avec succès'
        ]);
    } elseif ($profileType === 'candidat') {
        $candidat = $database->fetch(
            "SELECT id FROM cvs WHERE utilisateur_id = ?",
            [$userId]
        );

        if (!$candidat) {
            throw new Exception('Profil candidat non trouvé');
        }

        $database->delete(
            'cvs',
            'utilisateur_id = ?',
            [$userId]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Profil candidat supprimé avec succès'
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>