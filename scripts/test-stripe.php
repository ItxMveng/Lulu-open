<?php
/**
 * Script de test - Système Stripe LULU-OPEN
 * Vérification de l'installation et de la configuration
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/StripeGateway.php';

echo "🧪 Test du système Stripe LULU-OPEN\n";
echo "=====================================\n\n";

$errors = [];
$warnings = [];

// 1. Test de la base de données
echo "📊 Test de la base de données...\n";
try {
    $db = Database::getInstance();
    
    // Vérifier les tables
    $tables = ['demandes_upgrade', 'paiements_stripe', 'notifications'];
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        if (empty($result)) {
            $errors[] = "Table '$table' manquante";
        } else {
            echo "   ✅ Table '$table' présente\n";
        }
    }
    
    // Vérifier les colonnes Stripe dans utilisateurs
    $columns = $db->query("SHOW COLUMNS FROM utilisateurs LIKE 'stripe_%'");
    if (count($columns) >= 3) {
        echo "   ✅ Colonnes Stripe ajoutées à 'utilisateurs'\n";
    } else {
        $errors[] = "Colonnes Stripe manquantes dans 'utilisateurs'";
    }
    
} catch (Exception $e) {
    $errors[] = "Erreur base de données: " . $e->getMessage();
}

// 2. Test de la configuration Stripe
echo "\n🔧 Test de la configuration Stripe...\n";
if (defined('STRIPE_PUBLIC_KEY') && !empty(STRIPE_PUBLIC_KEY)) {
    echo "   ✅ Clé publique Stripe configurée\n";
} else {
    $errors[] = "Clé publique Stripe manquante";
}

if (defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY)) {
    if (STRIPE_SECRET_KEY === 'sk_live_51SkBdOQ2hn8SZbbY...') {
        $warnings[] = "Clé secrète Stripe par défaut - À remplacer";
    } else {
        echo "   ✅ Clé secrète Stripe configurée\n";
    }
} else {
    $errors[] = "Clé secrète Stripe manquante";
}

if (defined('PLANS_CONFIG') && !empty(PLANS_CONFIG)) {
    echo "   ✅ Configuration des plans définie\n";
    foreach (PLANS_CONFIG as $plan => $config) {
        if (strpos($config['stripe_price_id'], 'price_1QdqJfQ2hn8SZbbY') !== false) {
            $warnings[] = "Price ID par défaut pour le plan '$plan' - À créer dans Stripe Dashboard";
        }
    }
} else {
    $errors[] = "Configuration des plans manquante";
}

// 3. Test des fichiers
echo "\n📁 Test des fichiers...\n";
$files = [
    'config/stripe.php',
    'includes/StripeGateway.php',
    'controllers/PaymentController.php',
    'api/stripe-webhook.php',
    'views/payments.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        echo "   ✅ $file présent\n";
    } else {
        $errors[] = "Fichier '$file' manquant";
    }
}

// 4. Test des dépendances
echo "\n📦 Test des dépendances...\n";
if (class_exists('Stripe\Stripe')) {
    echo "   ✅ SDK Stripe installé\n";
} else {
    $errors[] = "SDK Stripe non installé - Exécuter 'composer install'";
}

// 5. Test des permissions
echo "\n🔒 Test des permissions...\n";
$directories = ['uploads/proofs', 'logs'];
foreach ($directories as $dir) {
    $path = __DIR__ . '/../' . $dir;
    if (is_dir($path) && is_writable($path)) {
        echo "   ✅ Dossier '$dir' accessible en écriture\n";
    } else {
        $warnings[] = "Dossier '$dir' non accessible en écriture";
    }
}

// 6. Test de connectivité (optionnel)
echo "\n🌐 Test de connectivité Stripe...\n";
if (function_exists('curl_init')) {
    echo "   ✅ cURL disponible\n";
    
    // Test basique de l'API Stripe (sans vraie requête)
    if (defined('STRIPE_SECRET_KEY') && STRIPE_SECRET_KEY !== 'sk_live_51SkBdOQ2hn8SZbbY...') {
        try {
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            echo "   ✅ Configuration Stripe valide\n";
        } catch (Exception $e) {
            $warnings[] = "Erreur configuration Stripe: " . $e->getMessage();
        }
    }
} else {
    $errors[] = "cURL non disponible - Requis pour Stripe";
}

// Résultats
echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 RÉSULTATS DU TEST\n";
echo str_repeat("=", 50) . "\n";

if (empty($errors)) {
    echo "✅ SUCCÈS - Installation Stripe complète !\n";
} else {
    echo "❌ ERREURS CRITIQUES (" . count($errors) . "):\n";
    foreach ($errors as $error) {
        echo "   • $error\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  AVERTISSEMENTS (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "   • $warning\n";
    }
}

echo "\n🔧 PROCHAINES ÉTAPES:\n";
echo "1. Compléter les clés Stripe dans config/stripe.php\n";
echo "2. Créer les prix dans le Dashboard Stripe\n";
echo "3. Configurer le webhook Stripe: " . (defined('STRIPE_WEBHOOK_URL') ? STRIPE_WEBHOOK_URL : APP_URL . '/api/stripe-webhook.php') . "\n";
echo "4. Tester un paiement en mode test\n";
echo "5. Passer en mode live quand tout fonctionne\n\n";

echo "🎉 Système Stripe LULU-OPEN prêt à être configuré !\n";
?>