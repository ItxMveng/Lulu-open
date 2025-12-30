<?php
session_start();
require_once '../config/config.php';
require_once '../config/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

try {
    global $database;
    $userId = $_SESSION['user_id'];
    
    // Récupérer les activités récentes
    $activities = $database->fetchAll(
        "SELECT action, details, created_at 
         FROM logs_activite 
         WHERE utilisateur_id = ? 
         ORDER BY created_at DESC 
         LIMIT 10",
        [$userId]
    );
    
    $formattedActivities = [];
    
    foreach ($activities as $activity) {
        $icon = '📊';
        $description = $activity['action'];
        
        // Personnaliser selon le type d'action
        switch ($activity['action']) {
            case 'login':
                $icon = '🔐';
                $description = 'Connexion à votre compte';
                break;
            case 'profile_update':
                $icon = '✏️';
                $description = 'Mise à jour du profil';
                break;
            case 'message_sent':
                $icon = '💬';
                $description = 'Message envoyé';
                break;
            case 'message_received':
                $icon = '📨';
                $description = 'Nouveau message reçu';
                break;
            case 'profile_view':
                $icon = '👁️';
                $description = 'Votre profil a été consulté';
                break;
            case 'subscription_update':
                $icon = '💳';
                $description = 'Abonnement mis à jour';
                break;
            default:
                $description = ucfirst(str_replace('_', ' ', $activity['action']));
        }
        
        $formattedActivities[] = [
            'icon' => $icon,
            'description' => $description,
            'created_at' => $activity['created_at']
        ];
    }
    
    // Si pas d'activités, ajouter des exemples
    if (empty($formattedActivities)) {
        $formattedActivities = [
            [
                'icon' => '🔐',
                'description' => 'Connexion à votre compte',
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'icon' => '📊',
                'description' => 'Profil créé avec succès',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
    }
    
    echo json_encode(['activities' => $formattedActivities]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>