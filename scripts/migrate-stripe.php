<?php
/**
 * Migration Base de Données - Système Stripe
 * LULU-OPEN - Paiements automatisés
 */

require_once __DIR__ . '/../config/config.php';

try {
    $db = Database::getInstance();
    
    echo "🚀 Début de la migration Stripe...\n";
    
    // 1. Ajouter les colonnes Stripe aux utilisateurs
    echo "📝 Ajout des colonnes Stripe aux utilisateurs...\n";
    
    // Vérifier et ajouter chaque colonne individuellement
    try {
        $db->query("ALTER TABLE `utilisateurs` ADD COLUMN `stripe_customer_id` VARCHAR(255) NULL");
        echo "   ✅ Colonne stripe_customer_id ajoutée\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ⚠️ Colonne stripe_customer_id existe déjà\n";
    }
    
    try {
        $db->query("ALTER TABLE `utilisateurs` ADD COLUMN `stripe_subscription_id` VARCHAR(255) NULL");
        echo "   ✅ Colonne stripe_subscription_id ajoutée\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ⚠️ Colonne stripe_subscription_id existe déjà\n";
    }
    
    try {
        $db->query("ALTER TABLE `utilisateurs` ADD COLUMN `stripe_payment_status` ENUM('pending','succeeded','failed') DEFAULT NULL");
        echo "   ✅ Colonne stripe_payment_status ajoutée\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            throw $e;
        }
        echo "   ⚠️ Colonne stripe_payment_status existe déjà\n";
    }
    
    // 2. Créer la table demandes_upgrade
    echo "📝 Création de la table demandes_upgrade...\n";
    
    $db->query("
        CREATE TABLE IF NOT EXISTS `demandes_upgrade` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `utilisateur_id` INT NOT NULL,
            `plan_demande` ENUM('monthly','quarterly','yearly') NOT NULL,
            `montant` DECIMAL(10,2) NOT NULL,
            `stripe_session_id` VARCHAR(255) NULL,
            `stripe_payment_intent` VARCHAR(255) NULL,
            `statut` ENUM('en_attente','paye','approuve','refuse') DEFAULT 'en_attente',
            `date_demande` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `date_traitement` DATETIME NULL,
            `traite_par_admin_id` INT NULL,
            `motif_refus` TEXT NULL,
            INDEX `idx_utilisateur` (`utilisateur_id`),
            INDEX `idx_statut` (`statut`),
            INDEX `idx_stripe_session` (`stripe_session_id`),
            FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 3. Créer la table paiements_stripe
    echo "📝 Création de la table paiements_stripe...\n";
    
    $db->query("
        CREATE TABLE IF NOT EXISTS `paiements_stripe` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `utilisateur_id` INT NOT NULL,
            `demande_upgrade_id` INT NULL,
            `montant` DECIMAL(10,2) NOT NULL,
            `plan` ENUM('monthly','quarterly','yearly') NOT NULL,
            `stripe_session_id` VARCHAR(255) NULL,
            `stripe_payment_intent` VARCHAR(255) NULL,
            `status` ENUM('pending','succeeded','failed','refunded') DEFAULT 'pending',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_utilisateur` (`utilisateur_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_stripe_session` (`stripe_session_id`),
            INDEX `idx_stripe_payment_intent` (`stripe_payment_intent`),
            FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`demande_upgrade_id`) REFERENCES `demandes_upgrade`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 4. Créer la table notifications si elle n'existe pas
    echo "📝 Vérification de la table notifications...\n";
    
    $db->query("
        CREATE TABLE IF NOT EXISTS `notifications` (
            `id` INT PRIMARY KEY AUTO_INCREMENT,
            `utilisateur_id` INT NOT NULL,
            `type_notification` ENUM('systeme','paiement','abonnement','message') DEFAULT 'systeme',
            `titre` VARCHAR(255) NOT NULL,
            `contenu` TEXT NOT NULL,
            `lu` BOOLEAN DEFAULT FALSE,
            `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_utilisateur` (`utilisateur_id`),
            INDEX `idx_lu` (`lu`),
            FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // 5. Mettre à jour les utilisateurs existants avec le statut gratuit
    echo "📝 Mise à jour des utilisateurs existants...\n";
    
    $db->query("
        UPDATE `utilisateurs` 
        SET `subscription_status` = 'free' 
        WHERE `subscription_status` IS NULL OR `subscription_status` = ''
    ");
    
    // 6. Créer des index pour optimiser les performances
    echo "📝 Création des index d'optimisation...\n";
    
    try {
        $db->query("ALTER TABLE `utilisateurs` ADD INDEX `idx_subscription_status` (`subscription_status`)");
        echo "   ✅ Index subscription_status ajouté\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') === false) {
            echo "   ⚠️ Index subscription_status: " . $e->getMessage() . "\n";
        } else {
            echo "   ⚠️ Index subscription_status existe déjà\n";
        }
    }
    
    try {
        $db->query("ALTER TABLE `utilisateurs` ADD INDEX `idx_subscription_end_date` (`subscription_end_date`)");
        echo "   ✅ Index subscription_end_date ajouté\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key') === false) {
            echo "   ⚠️ Index subscription_end_date: " . $e->getMessage() . "\n";
        } else {
            echo "   ⚠️ Index subscription_end_date existe déjà\n";
        }
    }
    
    echo "✅ Migration Stripe terminée avec succès !\n";
    echo "\n📋 Résumé des modifications :\n";
    echo "   • Colonnes Stripe ajoutées à 'utilisateurs'\n";
    echo "   • Table 'demandes_upgrade' créée\n";
    echo "   • Table 'paiements_stripe' créée\n";
    echo "   • Table 'notifications' vérifiée\n";
    echo "   • Index d'optimisation ajoutés\n";
    echo "   • Utilisateurs existants mis à jour\n";
    echo "\n🔧 Prochaines étapes :\n";
    echo "   1. Configurer les clés Stripe dans config/stripe.php\n";
    echo "   2. Créer les prix dans le dashboard Stripe\n";
    echo "   3. Configurer le webhook Stripe\n";
    echo "   4. Tester les paiements\n\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la migration : " . $e->getMessage() . "\n";
    echo "📍 Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}
?>