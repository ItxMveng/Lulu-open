<?php
/**
 * Script de création de la table abonnements
 */

require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance()->getConnection();

echo "🔧 Création/Vérification de la table abonnements\n";
echo "===============================================\n\n";

try {
    // Vérifier si la table existe
    $tableExists = $db->query("SHOW TABLES LIKE 'abonnements'")->rowCount() > 0;
    
    if (!$tableExists) {
        echo "Création de la table abonnements...\n";
        
        $sql = "CREATE TABLE `abonnements` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `utilisateur_id` int(11) NOT NULL,
            `plan` varchar(50) NOT NULL DEFAULT 'gratuit',
            `statut` enum('Actif','Inactif','Expiré') NOT NULL DEFAULT 'Actif',
            `date_debut` datetime NOT NULL,
            `date_fin` datetime NOT NULL,
            `montant` decimal(10,2) NOT NULL DEFAULT 0.00,
            `stripe_subscription_id` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_utilisateur` (`utilisateur_id`),
            KEY `idx_statut` (`statut`),
            KEY `idx_date_fin` (`date_fin`),
            FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅ Table abonnements créée avec succès\n";
    } else {
        echo "✅ Table abonnements existe déjà\n";
        
        // Vérifier les colonnes nécessaires
        $columns = $db->query("DESCRIBE abonnements")->fetchAll(PDO::FETCH_COLUMN);
        $required_columns = ['plan', 'statut', 'date_debut', 'date_fin', 'montant'];
        
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                echo "Ajout de la colonne manquante: $col\n";
                
                switch ($col) {
                    case 'plan':
                        $db->exec("ALTER TABLE abonnements ADD COLUMN plan varchar(50) NOT NULL DEFAULT 'gratuit'");
                        break;
                    case 'statut':
                        $db->exec("ALTER TABLE abonnements ADD COLUMN statut enum('Actif','Inactif','Expiré') NOT NULL DEFAULT 'Actif'");
                        break;
                    case 'date_debut':
                        $db->exec("ALTER TABLE abonnements ADD COLUMN date_debut datetime NOT NULL");
                        break;
                    case 'date_fin':
                        $db->exec("ALTER TABLE abonnements ADD COLUMN date_fin datetime NOT NULL");
                        break;
                    case 'montant':
                        $db->exec("ALTER TABLE abonnements ADD COLUMN montant decimal(10,2) NOT NULL DEFAULT 0.00");
                        break;
                }
                echo "✅ Colonne $col ajoutée\n";
            }
        }
    }
    
    // Vérifier si la table admin_logs existe
    $adminLogsExists = $db->query("SHOW TABLES LIKE 'admin_logs'")->rowCount() > 0;
    
    if (!$adminLogsExists) {
        echo "\nCréation de la table admin_logs...\n";
        
        $sql = "CREATE TABLE `admin_logs` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `admin_id` int(11) NOT NULL,
            `action` varchar(100) NOT NULL,
            `cible_type` varchar(50) DEFAULT NULL,
            `cible_id` int(11) DEFAULT NULL,
            `details` json DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_admin` (`admin_id`),
            KEY `idx_action` (`action`),
            KEY `idx_created_at` (`created_at`),
            FOREIGN KEY (`admin_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $db->exec($sql);
        echo "✅ Table admin_logs créée avec succès\n";
    }
    
    echo "\n✅ Toutes les tables sont prêtes !\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>