<?php
/**
 * Script pour ajouter la colonne fichier_joint à la table messages
 */

require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Mise à jour de la table messages\n";
echo "===================================\n\n";

try {
    // Vérifier si la colonne existe déjà
    $stmt = $db->query("DESCRIBE messages");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('fichier_joint', $columns)) {
        echo "Ajout de la colonne fichier_joint...\n";
        
        $sql = "ALTER TABLE messages ADD COLUMN fichier_joint VARCHAR(255) NULL AFTER contenu";
        $db->exec($sql);
        
        echo "✅ Colonne fichier_joint ajoutée avec succès\n";
    } else {
        echo "✅ Colonne fichier_joint existe déjà\n";
    }
    
    echo "\n✅ Mise à jour terminée !\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>