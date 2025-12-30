<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$userId) {
    echo json_encode(['success' => false, 'error' => 'ID manquant']);
    exit;
}

global $database;
$user = $database->fetch(
    "SELECT id, nom, prenom, photo_profil FROM utilisateurs WHERE id = ? AND statut = 'actif'",
    [$userId]
);

if ($user) {
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'error' => 'Utilisateur introuvable']);
}
?>
