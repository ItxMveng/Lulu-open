<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

global $database;
$userId = $_SESSION['user_id'];
$contactId = $_GET['contact_id'] ?? null;

if (!$contactId) {
    echo json_encode(['success' => false, 'error' => 'Contact ID manquant']);
    exit;
}

try {
    $messages = $database->fetchAll("
        SELECT m.id, m.contenu, m.expediteur_id, m.destinataire_id, m.date_envoi as created_at,
               CASE WHEN m.expediteur_id = ? THEN 1 ELSE 0 END as is_sent
        FROM messages m
        WHERE (m.expediteur_id = ? AND m.destinataire_id = ?) 
            OR (m.expediteur_id = ? AND m.destinataire_id = ?)
        ORDER BY m.id ASC
    ", [$userId, $userId, $contactId, $contactId, $userId]);
    
    $database->query(
        "UPDATE messages SET lu = 1 WHERE destinataire_id = ? AND expediteur_id = ?",
        [$userId, $contactId]
    );
    
    echo json_encode(['success' => true, 'messages' => $messages]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>