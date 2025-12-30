<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

global $database;

$contact_id = $_GET['contact'] ?? null;
$last_check = $_GET['last_check'] ?? date('Y-m-d H:i:s', strtotime('-1 minute'));

if (!$contact_id) {
    echo json_encode(['error' => 'Contact ID manquant']);
    exit;
}

try {
    // Vérifier s'il y a de nouveaux messages depuis la dernière vérification
    $new_messages = $database->fetchAll("
        SELECT COUNT(*) as count
        FROM messages 
        WHERE expediteur_id = ? 
        AND destinataire_id = ? 
        AND date_envoi > ?
    ", [$contact_id, $_SESSION['user_id'], $last_check]);
    
    $has_new_messages = $new_messages[0]['count'] > 0;
    
    echo json_encode([
        'new_messages' => $has_new_messages,
        'count' => $new_messages[0]['count'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur']);
}
?>