<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "🔍 Diagnostic de la colonne subscription_status\n\n";
    
    // Vérifier la définition de la colonne
    $stmt = $db->query("SHOW COLUMNS FROM utilisateurs WHERE Field = 'subscription_status'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "📋 Définition de la colonne:\n";
        foreach ($column as $key => $value) {
            echo "   $key: $value\n";
        }
        echo "\n";
    }
    
    // Tester différentes valeurs
    $testValues = ['active', 'gratuit', 'premium', 'Actif'];
    
    foreach ($testValues as $value) {
        echo "🧪 Test avec valeur: '$value'\n";
        
        try {
            $stmt = $db->prepare("UPDATE utilisateurs SET subscription_status = ? WHERE id = 1");
            $result = $stmt->execute([$value]);
            
            echo "   Résultat: " . ($result ? "SUCCESS" : "FAILED") . "\n";
            
            // Vérifier la valeur
            $check = $db->query("SELECT subscription_status FROM utilisateurs WHERE id = 1")->fetchColumn();
            echo "   Valeur stockée: '$check'\n";
            
        } catch (Exception $e) {
            echo "   Erreur: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    // Vérifier les contraintes
    echo "🔒 Vérification des contraintes:\n";
    $stmt = $db->query("
        SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE TABLE_SCHEMA = 'lulu_open' AND TABLE_NAME = 'utilisateurs'
    ");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($constraints as $constraint) {
        echo "   {$constraint['CONSTRAINT_TYPE']}: {$constraint['CONSTRAINT_NAME']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?>