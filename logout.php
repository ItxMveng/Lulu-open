<?php
session_start();
require_once 'config/config.php';

// Log de déconnexion si l'utilisateur était connecté
if (isset($_SESSION['user_id'])) {
    require_once 'config/db.php';
    
    try {
        global $database;
        $database->insert('logs_activite', [
            'utilisateur_id' => $_SESSION['user_id'],
            'action' => 'logout',
            'details' => 'Déconnexion utilisateur',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        // Ignorer les erreurs de log
    }
}

// Détruire la session
session_destroy();

// Message de confirmation
session_start();
flashMessage('Déconnexion réussie', 'info');

// Redirection vers la page de connexion
header('Location: /lulu/login.php');
exit;
?>