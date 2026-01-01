<?php
/**
 * Script de test pour activer manuellement un abonnement
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance();
    
    echo "🧪 Test d'activation d'abonnement\n\n";
    
    // Récupérer un utilisateur de test
    $user = $db->fetch("SELECT id, email, subscription_status FROM utilisateurs WHERE email LIKE '%@%' LIMIT 1");
    
    if (!$user) {
        echo "❌ Aucun utilisateur trouvé\n";
        exit;
    }
    
    echo "👤 Utilisateur test: {$user['email']} (ID: {$user['id']})\n";
    echo "📊 Status actuel: {$user['subscription_status']}\n\n";
    
    // Test d'activation
    $userId = $user['id'];
    $plan = 'monthly';
    $startDate = date('Y-m-d H:i:s');
    $endDate = date('Y-m-d H:i:s', strtotime('+1 month'));
    
    echo "🔧 Tentative d'activation...\n";
    echo "   Plan: $plan\n";
    echo "   Début: $startDate\n";
    echo "   Fin: $endDate\n\n";
    
    // Mise à jour
    $result = $db->query(
        "UPDATE utilisateurs SET 
            subscription_status = 'Actif',
            subscription_start_date = ?,
            subscription_end_date = ?
        WHERE id = ?",
        [$startDate, $endDate, $userId]
    );
    
    echo "📝 Résultat UPDATE: " . ($result ? "SUCCESS" : "FAILED") . "\n\n";
    
    // Vérification
    $updatedUser = $db->fetch("SELECT subscription_status, subscription_start_date, subscription_end_date FROM utilisateurs WHERE id = ?", [$userId]);
    
    echo "✅ Utilisateur après update:\n";
    echo "   Status: {$updatedUser['subscription_status']}\n";
    echo "   Début: {$updatedUser['subscription_start_date']}\n";
    echo "   Fin: {$updatedUser['subscription_end_date']}\n\n";
    
    // Test de vérification d'abonnement
    require_once __DIR__ . '/../includes/StripeGateway.php';
    
    $isSubscribed = StripeGateway::isSubscribed($userId);
    echo "🔍 Test isSubscribed(): " . ($isSubscribed ? "TRUE" : "FALSE") . "\n";
    
    $subInfo = StripeGateway::getSubscriptionInfo($userId);
    echo "📋 Info abonnement: " . json_encode($subInfo, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?>