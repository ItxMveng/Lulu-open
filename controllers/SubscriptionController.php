<?php
declare(strict_types=1);

final class SubscriptionController extends Controller
{
    private Subscription $subscriptions;
    private StripeGateway $gateway;

    public function __construct()
    {
        $this->subscriptions = new Subscription();
        $this->gateway = new StripeGateway();
    }

    public function showPlans(): void
    {
        $plans = $this->subscriptions->activePlans();
        $grouped = ['client' => [], 'entreprise' => []];
        foreach ($plans as $plan) {
            $target = (string) ($plan['role_target'] ?? 'client');
            $grouped[$target][] = $plan;
        }

        $this->render('pages/pricing', [
            'title' => 'Tarifs',
            'fullWidth' => true,
            'clientPlans' => $grouped['client'],
            'entreprisePlans' => $grouped['entreprise'],
        ]);
    }

    public function checkout(string $planId): never
    {
        AuthMiddleware::requireAuth();
        $url = $this->gateway->createCheckoutSession((int) current_user_id(), $planId, url('/abonnement/success'), url('/abonnement/cancel'));
        redirect($url);
    }

    public function success(): void
    {
        AuthMiddleware::requireAuth();
        $this->render('pages/pricing', ['title' => 'Paiement confirmé']);
    }

    public function cancel(): void
    {
        AuthMiddleware::requireAuth();
        flash('Le paiement a été annulé.', 'warning');
        redirect('/abonnement');
    }

    public function portal(): never
    {
        AuthMiddleware::requireAuth();
        $user = (new User())->findById((int) current_user_id());
        $url = $this->gateway->createCustomerPortalSession((string) ($user['stripe_customer_id'] ?? ''));
        redirect($url);
    }

    public function current(): void
    {
        AuthMiddleware::requireAuth();
        $subscription = $this->subscriptions->getCurrentByUserId((int) current_user_id());
        $view = current_role() === 'entreprise' ? 'entreprise/subscription' : 'client/subscription';
        $this->render($view, ['title' => 'Mon abonnement', 'subscription' => $subscription]);
    }
}