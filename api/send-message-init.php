<?php
session_start();
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'redirect' => '/lulu/login.php']);
    exit;
}

global $database;
$senderId = $_SESSION['user_id'];
$recipientId = $_POST['recipient_id'] ?? 0;

try {
    if ($senderId == $recipientId) {
        throw new Exception('Vous ne pouvez pas vous envoyer un message à vous-même');
    }
    
    $recipient = $database->fetch("SELECT id, type_utilisateur FROM utilisateurs WHERE id = ?", [$recipientId]);
    if (!$recipient) {
        throw new Exception('Utilisateur non trouvé');
    }
    
    // Créer une conversation initiale si elle n'existe pas
    $existingConv = $database->fetch(
        "SELECT id FROM messages WHERE 
         (expediteur_id = ? AND destinataire_id = ?) OR 
         (expediteur_id = ? AND destinataire_id = ?) 
         LIMIT 1",
        [$senderId, $recipientId, $recipientId, $senderId]
    );
    
    if (!$existingConv) {
        // Créer un message initial
        $database->insert('messages', [
            'expediteur_id' => $senderId,
            'destinataire_id' => $recipientId,
            'sujet' => 'Nouveau contact',
            'contenu' => 'Bonjour, je souhaite entrer en contact avec vous.',
            'lu' => 0,
            'date_envoi' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Déterminer l'URL de redirection selon le type d'utilisateur
    $userType = $_SESSION['user_type'];
    $redirectUrl = match($userType) {
        'admin' => '/lulu/views/admin/messages.php?to=' . $recipientId,
        'prestataire' => '/lulu/views/prestataire/messages/inbox.php?to=' . $recipientId,
        'candidat' => '/lulu/views/candidat/messages.php?to=' . $recipientId,
        'client' => '/lulu/views/client/messages.php?to=' . $recipientId,
        'prestataire_candidat' => '/lulu/views/prestataire_candidat/messages/inbox.php?to=' . $recipientId,
        default => '/lulu/messages.php?to=' . $recipientId
    };
    
    echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
