<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once base_path('includes/ErrorHandler.php');

ErrorHandler::register();
require_once base_path('config/stripe.php');

$payload = (string) file_get_contents('php://input');
$signature = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
$gateway = new StripeGateway();
$subscriptions = new Subscription();

try {
    $event = $gateway->handleWebhook($payload, $signature);
    $type = (string) ($event['type'] ?? 'unknown');
    $data = $event['data']['object'] ?? [];

    file_put_contents(LOG_PATH . DIRECTORY_SEPARATOR . 'stripe_webhooks.log', json_encode(['timestamp' => date('c'), 'type' => $type, 'data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

    if ($type === 'checkout.session.completed') {
        $userId = (int) ($data['metadata']['user_id'] ?? 0);
        $planSlug = (string) ($data['metadata']['plan_slug'] ?? '');
        if ($userId > 0 && $planSlug !== '') {
            $subscriptions->activatePlan($userId, $planSlug, 'active', (string) ($data['customer'] ?? ''), (string) ($data['subscription'] ?? ''), null);
        }
    }

    if ($type === 'customer.subscription.updated') {
        $subscriptions->updateByStripeSubscription((string) ($data['id'] ?? ''), (string) ($data['status'] ?? 'active'));
    }

    if ($type === 'customer.subscription.deleted') {
        $statement = db()->prepare('SELECT users.id, users.role FROM subscriptions INNER JOIN users ON users.id = subscriptions.user_id WHERE subscriptions.stripe_subscription_id = :stripe_subscription_id ORDER BY subscriptions.id DESC LIMIT 1');
        $statement->execute(['stripe_subscription_id' => (string) ($data['id'] ?? '')]);
        $user = $statement->fetch();
        if ($user) {
            $subscriptions->downgradeToFree((int) $user['id'], (string) $user['role']);
        }
    }

    if ($type === 'invoice.payment_failed') {
        $statement = db()->prepare('INSERT INTO activity_logs (user_id, action, metadata, created_at) VALUES (NULL, :action, :metadata, NOW())');
        $statement->execute([
            'action' => 'stripe_payment_failed',
            'metadata' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }
} catch (Throwable $throwable) {
    file_put_contents(LOG_PATH . DIRECTORY_SEPARATOR . 'stripe_webhooks.log', json_encode(['timestamp' => date('c'), 'error' => $throwable->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);
}

http_response_code(200);
echo 'ok';