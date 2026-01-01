<?php
/**
 * Script CRON - Gestion automatique des abonnements
 * À exécuter quotidiennement pour :
 * - Détecter les abonnements expirés
 * - Créer automatiquement des abonnements gratuits de remplacement
 * - Envoyer des notifications d'expiration
 */

require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance()->getConnection();

// Log de démarrage
$log_file = __DIR__ . '/../logs/subscription_cron.log';
$log_entry = date('Y-m-d H:i:s') . " - Démarrage du script de gestion des abonnements\n";
file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

try {
    $db->beginTransaction();
    
    // 1. Détecter et traiter les abonnements payants expirés
    $expired_paid_subscriptions = $db->query("
        SELECT a.*, u.prenom, u.nom, u.email 
        FROM abonnements a
        JOIN utilisateurs u ON a.utilisateur_id = u.id
        WHERE a.date_fin < NOW() 
        AND a.statut = 'Actif' 
        AND a.plan != 'gratuit'
    ");
    
    foreach ($expired_paid_subscriptions as $subscription) {
        // Marquer l'abonnement comme expiré
        $db->prepare("
            UPDATE abonnements 
            SET statut = 'Expiré' 
            WHERE id = ?
        ")->execute([$subscription['id']]);
        
        // Créer un nouvel abonnement gratuit d'1 an
        $start_date = date('Y-m-d H:i:s');
        $end_date = date('Y-m-d H:i:s', strtotime('+1 year'));
        
        $db->prepare("
            INSERT INTO abonnements (
                utilisateur_id, 
                plan, 
                statut, 
                date_debut, 
                date_fin, 
                montant, 
                created_at
            ) VALUES (?, 'gratuit', 'Actif', ?, ?, 0.00, NOW())
        ")->execute([$subscription['utilisateur_id'], $start_date, $end_date]);
        
        // Créer une notification pour l'utilisateur
        $db->prepare("
            INSERT INTO notifications (
                utilisateur_id, 
                titre, 
                message, 
                type, 
                created_at
            ) VALUES (?, ?, ?, 'info', NOW())
        ")->execute([
            $subscription['utilisateur_id'],
            'Abonnement expiré - Passage au plan gratuit',
            'Votre abonnement premium a expiré. Vous êtes maintenant sur le plan gratuit. Vous pouvez renouveler votre abonnement à tout moment depuis votre tableau de bord.'
        ]);
        
        $log_entry = date('Y-m-d H:i:s') . " - Abonnement expiré traité pour {$subscription['prenom']} {$subscription['nom']} (ID: {$subscription['utilisateur_id']})\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    // 2. Étendre les abonnements gratuits expirés
    $expired_free_count = $db->prepare("
        UPDATE abonnements 
        SET date_fin = DATE_ADD(NOW(), INTERVAL 1 YEAR)
        WHERE plan = 'gratuit' 
        AND date_fin < NOW() 
        AND statut = 'Actif'
    ");
    $expired_free_count->execute();
    $extended_count = $expired_free_count->rowCount();
    
    if ($extended_count > 0) {
        $log_entry = date('Y-m-d H:i:s') . " - {$extended_count} abonnements gratuits étendus\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    // 3. Notifications d'expiration prochaine (7 jours avant)
    $expiring_soon = $db->query("
        SELECT a.*, u.prenom, u.nom, u.email,
               DATEDIFF(a.date_fin, NOW()) as jours_restants
        FROM abonnements a
        JOIN utilisateurs u ON a.utilisateur_id = u.id
        WHERE a.statut = 'Actif' 
        AND a.plan != 'gratuit'
        AND DATEDIFF(a.date_fin, NOW()) = 7
        AND NOT EXISTS (
            SELECT 1 FROM notifications n 
            WHERE n.utilisateur_id = u.id 
            AND n.titre LIKE '%expiration dans 7 jours%'
            AND DATE(n.created_at) = CURDATE()
        )
    ");
    
    foreach ($expiring_soon as $subscription) {
        $db->prepare("
            INSERT INTO notifications (
                utilisateur_id, 
                titre, 
                message, 
                type, 
                created_at
            ) VALUES (?, ?, ?, 'warning', NOW())
        ")->execute([
            $subscription['utilisateur_id'],
            'Abonnement - Expiration dans 7 jours',
            'Votre abonnement premium expire dans 7 jours. Renouvelez-le dès maintenant pour continuer à profiter de tous les avantages.'
        ]);
        
        $log_entry = date('Y-m-d H:i:s') . " - Notification d'expiration envoyée à {$subscription['prenom']} {$subscription['nom']}\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    $db->commit();
    
    $log_entry = date('Y-m-d H:i:s') . " - Script terminé avec succès\n\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    $db->rollback();
    $log_entry = date('Y-m-d H:i:s') . " - ERREUR: " . $e->getMessage() . "\n\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}
?>