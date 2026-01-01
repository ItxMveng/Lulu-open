<?php
/**
 * API Admin - Suivi des abonnements Stripe en temps réel
 */

require_once '../config/config.php';
require_once '../includes/middleware-admin.php';
require_admin();

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    
    // Statistiques temps réel
    $stats = [
        'nouveaux_aujourd_hui' => $db->fetch("
            SELECT COUNT(*) as count 
            FROM utilisateurs 
            WHERE subscription_status = 'active' 
            AND DATE(subscription_start_date) = CURDATE()
        ")['count'],
        
        'revenus_aujourd_hui' => $db->fetch("
            SELECT COALESCE(SUM(montant), 0) as total 
            FROM paiements_stripe 
            WHERE status = 'succeeded' 
            AND DATE(created_at) = CURDATE()
        ")['total'],
        
        'abonnements_actifs' => $db->fetch("
            SELECT COUNT(*) as count 
            FROM utilisateurs 
            WHERE subscription_status = 'active' 
            AND subscription_end_date > NOW()
        ")['count']
    ];
    
    // Derniers abonnements (5 derniers)
    $recent_subscriptions = $db->fetchAll("
        SELECT u.id, u.prenom, u.nom, u.email, u.subscription_plan, 
               u.subscription_start_date, ps.montant
        FROM utilisateurs u
        LEFT JOIN paiements_stripe ps ON u.id = ps.utilisateur_id
        WHERE u.subscription_status = 'active'
        ORDER BY u.subscription_start_date DESC
        LIMIT 5
    ");
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'recent_subscriptions' => $recent_subscriptions,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>