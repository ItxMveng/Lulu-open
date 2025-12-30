<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentification requise']);
    exit;
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

global $database;

if ($method === 'POST' && $action === 'add') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cible_id = $data['cible_id'] ?? null;
    $type_cible = $data['type_cible'] ?? 'prestataire';

    if (!$cible_id) {
        http_response_code(400);
        echo json_encode(['error' => 'cible_id manquant']);
        exit;
    }

    // Vérifier si pas déjà en favoris
    $existing = $database->fetch("SELECT id FROM favoris WHERE utilisateur_id = ? AND cible_id = ?", [
        $_SESSION['user_id'], $cible_id
    ]);

    if ($existing) {
        echo json_encode(['status' => 'already_added']);
        exit;
    }

    // Ajouter aux favoris
    try {
        $database->query(
            "INSERT INTO favoris (utilisateur_id, cible_id, type_cible) VALUES (?, ?, ?)",
            [$_SESSION['user_id'], $cible_id, $type_cible]
        );
        echo json_encode(['status' => 'added']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de l\'ajout']);
    }

} elseif ($method === 'DELETE' && $action === 'remove') {
    $cible_id = $_GET['cible_id'] ?? null;

    if (!$cible_id) {
        http_response_code(400);
        echo json_encode(['error' => 'cible_id manquant']);
        exit;
    }

    try {
        $database->query(
            "DELETE FROM favoris WHERE utilisateur_id = ? AND cible_id = ?",
            [$_SESSION['user_id'], $cible_id]
        );
        echo json_encode(['status' => 'removed']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors de la suppression']);
    }

} elseif ($method === 'GET' && $action === 'check') {
    $cible_id = $_GET['cible_id'] ?? null;

    if (!$cible_id) {
        http_response_code(400);
        echo json_encode(['error' => 'cible_id manquant']);
        exit;
    }

    $favori = $database->fetch(
        "SELECT id FROM favoris WHERE utilisateur_id = ? AND cible_id = ?",
        [$_SESSION['user_id'], $cible_id]
    );

    echo json_encode(['is_favorite' => (bool)$favori]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Action non reconnue']);
}
?>
