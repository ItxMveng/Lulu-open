<?php
require_once '../config/config.php';

// Vérification de connexion pour API
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expirée']);
    exit;
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];
$user = $database->fetch("SELECT statut FROM utilisateurs WHERE id = ?", [$userId]);

// Pour simplifier, on considère que si statut != 'en_attente', c'est actif
$active = $user['statut'] !== 'en_attente';
$days_remaining = 30; // Valeur par défaut

echo json_encode([
    'active' => $active,
    'days_remaining' => $days_remaining
]);
?>