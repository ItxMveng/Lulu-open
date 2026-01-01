<?php
/**
 * EXEMPLE DE CONFIGURATION STRIPE POUR TESTS LOCAUX
 * Remplacez les valeurs par vos vraies clés de test Stripe
 */

// 🔐 VOS CLÉS DE TEST STRIPE (à récupérer du Dashboard)
define('STRIPE_SECRET_KEY', 'sk_test_VOTRE_CLE_SECRETE_TEST');
define('STRIPE_PUBLIC_KEY', 'pk_test_VOTRE_CLE_PUBLIQUE_TEST');
define('STRIPE_WEBHOOK_SECRET', 'whsec_VOTRE_WEBHOOK_SECRET_TEST');

// 💰 VOS PRICE IDs DE TEST (à créer dans Stripe Dashboard mode test)
define('PLANS_CONFIG', [
    'monthly' => [
        'price' => 29.99,
        'stripe_price_id' => 'price_VOTRE_PRICE_ID_MENSUEL_TEST',
        'period_months' => 1,
        'name' => 'Mensuel',
        'description' => '29,99€/mois',
        'savings' => 0
    ],
    'quarterly' => [
        'price' => 79.99,
        'stripe_price_id' => 'price_VOTRE_PRICE_ID_TRIMESTRIEL_TEST',
        'period_months' => 3,
        'name' => 'Trimestriel',
        'description' => '79,99€ (26,66€/mois)',
        'savings' => 11
    ],
    'yearly' => [
        'price' => 299.00,
        'stripe_price_id' => 'price_VOTRE_PRICE_ID_ANNUEL_TEST',
        'period_months' => 12,
        'name' => 'Annuel',
        'description' => '299€ (24,91€/mois)',
        'savings' => 17
    ]
]);

/**
 * ÉTAPES POUR CONFIGURER :
 * 
 * 1. Dashboard Stripe → Mode Test
 * 2. Développeurs → Clés API → Copiez sk_test_... et pk_test_...
 * 3. Produits → Créer 3 prix (29.99€, 79.99€, 299€)
 * 4. Copiez les price_... générés
 * 5. Remplacez les valeurs ci-dessus
 * 6. Testez avec une carte test : 4242 4242 4242 4242
 */
?>