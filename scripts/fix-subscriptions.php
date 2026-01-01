<?php
/**
 * Script de correction du système d'abonnements
 * - Créer des abonnements gratuits par défaut pour tous les utilisateurs
 * - Gérer les abonnements expirés (retour au gratuit)
 * - Système de renouvellement automatique
 */

require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Correction du système d'abonnements LULU-OPEN\n";
echo "================================================\n\n";

try {
    $db->beginTransaction();

    // 1. Créer des abonnements gratuits pour tous les utilisateurs qui n'en ont pas
    echo "1. Création des abonnements gratuits par défaut...\n";
    
    $users_without_subscription = $db->query("
        SELECT u.id, u.prenom, u.nom, u.email, u.date_inscription 
        FROM utilisateurs u 
        WHERE NOT EXISTS (
            SELECT 1 FROM abonnements a 
            WHERE a.utilisateur_id = u.id
        )
    ");

    foreach ($users_without_subscription as $user) {
        // Créer un abonnement gratuit d'1 an à partir de la date d'inscription
        $start_date = $user['date_inscription'];
        $end_date = date('Y-m-d H:i:s', strtotime($start_date . ' +1 year'));
        
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
        ")->execute([$user['id'], $start_date, $end_date]);
        
        echo "   ✓ Abonnement gratuit créé pour {$user['prenom']} {$user['nom']}\n";
    }

    // 2. Mettre à jour les abonnements expirés
    echo "\n2. Gestion des abonnements expirés...\n";
    
    // Marquer comme expirés les abonnements payants dont la date de fin est dépassée
    $expired_paid = $db->prepare("
        UPDATE abonnements 
        SET statut = 'Expiré' 
        WHERE date_fin < NOW() 
        AND statut = 'Actif' 
        AND plan != 'gratuit'
    ");
    $expired_paid->execute();
    $expired_count = $expired_paid->rowCount();
    echo "   ✓ {$expired_count} abonnements payants expirés mis à jour\n";

    // Créer de nouveaux abonnements gratuits pour les utilisateurs dont l'abonnement payant a expiré
    $users_with_expired_paid = $db->query("
        SELECT DISTINCT u.id, u.prenom, u.nom 
        FROM utilisateurs u
        JOIN abonnements a ON u.id = a.utilisateur_id
        WHERE a.statut = 'Expiré' 
        AND a.plan != 'gratuit'
        AND NOT EXISTS (
            SELECT 1 FROM abonnements a2 
            WHERE a2.utilisateur_id = u.id 
            AND a2.statut = 'Actif'
        )
    ");

    foreach ($users_with_expired_paid as $user) {
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
        ")->execute([$user['id'], $start_date, $end_date]);
        
        echo "   ✓ Nouvel abonnement gratuit créé pour {$user['prenom']} {$user['nom']}\n";
    }

    // 3. Étendre les abonnements gratuits existants qui ont expiré
    echo "\n3. Extension des abonnements gratuits expirés...\n";
    
    $expired_free = $db->prepare("
        UPDATE abonnements 
        SET date_fin = DATE_ADD(NOW(), INTERVAL 1 YEAR),
            statut = 'Actif'
        WHERE plan = 'gratuit' 
        AND date_fin < NOW()
    ");
    $expired_free->execute();
    $extended_count = $expired_free->rowCount();
    echo "   ✓ {$extended_count} abonnements gratuits étendus d'1 an\n";

    // 4. Mettre à jour le statut des utilisateurs
    echo "\n4. Mise à jour du statut des utilisateurs...\n";
    
    // S'assurer que tous les utilisateurs avec un abonnement actif ont le statut 'Actif'
    $updated_users = $db->prepare("
        UPDATE utilisateurs u
        SET u.statut = 'Actif'
        WHERE EXISTS (
            SELECT 1 FROM abonnements a 
            WHERE a.utilisateur_id = u.id 
            AND a.statut = 'Actif'
        )
        AND u.statut != 'Actif'
    ");
    $updated_users->execute();
    $updated_count = $updated_users->rowCount();
    echo "   ✓ {$updated_count} utilisateurs mis à jour avec le statut 'Actif'\n";

    $db->commit();
    
    // 5. Statistiques finales
    echo "\n📊 Statistiques après correction :\n";
    echo "==================================\n";
    
    $stats = [
        'total_users' => $db->query("SELECT COUNT(*) as count FROM utilisateurs")->fetch()['count'],
        'active_subscriptions' => $db->query("SELECT COUNT(*) as count FROM abonnements WHERE statut = 'Actif'")->fetch()['count'],
        'free_subscriptions' => $db->query("SELECT COUNT(*) as count FROM abonnements WHERE statut = 'Actif' AND plan = 'gratuit'")->fetch()['count'],
        'paid_subscriptions' => $db->query("SELECT COUNT(*) as count FROM abonnements WHERE statut = 'Actif' AND plan != 'gratuit'")->fetch()['count'],
        'expired_subscriptions' => $db->query("SELECT COUNT(*) as count FROM abonnements WHERE statut = 'Expiré'")->fetch()['count']
    ];
    
    echo "Total utilisateurs : {$stats['total_users']}\n";
    echo "Abonnements actifs : {$stats['active_subscriptions']}\n";
    echo "  - Gratuits : {$stats['free_subscriptions']}\n";
    echo "  - Payants : {$stats['paid_subscriptions']}\n";
    echo "Abonnements expirés : {$stats['expired_subscriptions']}\n";
    
    echo "\n✅ Correction terminée avec succès !\n";
    
} catch (Exception $e) {
    $db->rollback();
    echo "\n❌ Erreur lors de la correction : " . $e->getMessage() . "\n";
    exit(1);
}
?>