<?php
declare(strict_types=1);

$autoload = base_path('vendor/autoload.php');
if (is_file($autoload)) {
    require_once $autoload;
}

if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', (string) env('STRIPE_SECRET_KEY', ''));
    define('STRIPE_PUBLISHABLE_KEY', (string) env('STRIPE_PUBLISHABLE_KEY', ''));
    define('STRIPE_WEBHOOK_SECRET', (string) env('STRIPE_WEBHOOK_SECRET', ''));
    define('STRIPE_PRICE_CLIENT_PRO', (string) env('STRIPE_PRICE_CLIENT_PRO', ''));
    define('STRIPE_PRICE_ENTREPRISE_PRO', (string) env('STRIPE_PRICE_ENTREPRISE_PRO', ''));
    define('STRIPE_PRICE_ENTREPRISE_BUSINESS', (string) env('STRIPE_PRICE_ENTREPRISE_BUSINESS', ''));
}

if (!defined('STRIPE_PRICE_MAP')) {
    define('STRIPE_PRICE_MAP', [
        'client_pro' => STRIPE_PRICE_CLIENT_PRO,
        'entreprise_pro' => STRIPE_PRICE_ENTREPRISE_PRO,
        'entreprise_business' => STRIPE_PRICE_ENTREPRISE_BUSINESS,
    ]);
}

if (class_exists('\\Stripe\\Stripe') && STRIPE_SECRET_KEY !== '') {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
}