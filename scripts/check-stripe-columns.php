<?php
/**
 * Script pour vérifier et ajouter les colonnes Stripe dans la table utilisateurs
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔍 Vérification de la structure de la table utilisateurs...\n\n";
    
    // Vérifier les colonnes existantes
    $stmt = $db->query("DESCRIBE utilisateurs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingColumns = array_column($columns, 'Field');
    
    echo "📋 Colonnes existantes :\n";
    foreach ($existingColumns as $col) {
        echo "  - $col\n";
    }
    
    // Colonnes nécessaires pour Stripe
    $requiredColumns = [
        'subscription_status' => "VARCHAR(20) DEFAULT 'gratuit'",
        'subscription_start_date' => "DATETIME NULL",
        'subscription_end_date' => "DATETIME NULL"
    ];
    
    echo "\n🔧 Vérification des colonnes Stripe...\n";
    
    foreach ($requiredColumns as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            echo "❌ Colonne manquante: $column\n";
            echo "   Ajout de la colonne...\n";
            
            $sql = "ALTER TABLE utilisateurs ADD COLUMN $column $definition";
            $db->exec($sql);
            
            echo "✅ Colonne $column ajoutée avec succès\n";
        } else {
            echo "✅ Colonne $column existe déjà\n";
        }
    }
    
    echo "\n📊 Test d'un utilisateur existant...\n";
    
    // Tester avec un utilisateur
    $user = $db->query("SELECT id, email, subscription_status, subscription_start_date, subscription_end_date FROM utilisateurs LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "👤 Utilisateur test: {$user['email']}\n";
        echo "   Status: " . ($user['subscription_status'] ?? 'NULL') . "\n";
        echo "   Début: " . ($user['subscription_start_date'] ?? 'NULL') . "\n";
        echo "   Fin: " . ($user['subscription_end_date'] ?? 'NULL') . "\n";
    }
    
    echo "\n✅ Vérification terminée avec succès !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?>