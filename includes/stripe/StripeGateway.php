<?php
declare(strict_types=1);

require_once base_path('config/stripe.php');

final class StripeGateway
{
    public function createCheckoutSession(int $userId, string $planId, string $successUrl, string $cancelUrl): string
    {
        $subscriptionModel = new Subscription();
        $plan = $subscriptionModel->findPlanBySlug($planId);
        if (!$plan) {
            throw new RuntimeException('Plan introuvable.');
        }

        $priceId = STRIPE_PRICE_MAP[$planId] ?? $plan['stripe_price_id'] ?? '';
        if ($priceId === '' || !class_exists('\\Stripe\\Checkout\\Session')) {
            return $successUrl . '?plan=' . urlencode($planId);
        }

        $session = \Stripe\Checkout\Session::create([
            'mode' => 'subscription',
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'user_id' => (string) $userId,
                'plan_slug' => $planId,
            ],
        ]);

        return (string) $session->url;
    }

    public function createCustomerPortalSession(string $stripeCustomerId): string
    {
        if ($stripeCustomerId === '' || !class_exists('\\Stripe\\BillingPortal\\Session')) {
            return url('/abonnement');
        }

        $session = \Stripe\BillingPortal\Session::create([
            'customer' => $stripeCustomerId,
            'return_url' => url('/abonnement'),
        ]);

        return (string) $session->url;
    }

    public function cancelSubscription(string $stripeSubscriptionId): bool
    {
        if ($stripeSubscriptionId === '' || !class_exists('\\Stripe\\Subscription')) {
            return false;
        }

        \Stripe\Subscription::cancel($stripeSubscriptionId, []);
        return true;
    }

    public function getSubscriptionStatus(string $stripeSubscriptionId): ?string
    {
        if ($stripeSubscriptionId === '' || !class_exists('\\Stripe\\Subscription')) {
            return null;
        }

        $subscription = \Stripe\Subscription::retrieve($stripeSubscriptionId);
        return $subscription->status ?? null;
    }

    public function handleWebhook(string $payload, string $signature): array
    {
        if (class_exists('\\Stripe\\Webhook') && STRIPE_WEBHOOK_SECRET !== '') {
            $event = \Stripe\Webhook::constructEvent($payload, $signature, STRIPE_WEBHOOK_SECRET);
            return $event->toArray();
        }

        return json_decode($payload, true) ?: [];
    }
}