<?php
declare(strict_types=1);

final class AdminSubscriptionController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $subscriptions = (new Subscription())->allActive();
        $this->render('admin/subscriptions/index', ['title' => 'Abonnements', 'subscriptions' => $subscriptions], 'admin');
    }

    public function forceCancel(string $id): never
    {
        AuthMiddleware::requireRole(['admin']);
        $gateway = new StripeGateway();
        $subscription = db()->prepare('SELECT * FROM subscriptions WHERE id = :id LIMIT 1');
        $subscription->execute(['id' => $id]);
        $row = $subscription->fetch();
        if ($row) {
            $gateway->cancelSubscription((string) ($row['stripe_subscription_id'] ?? ''));
            db()->prepare('UPDATE subscriptions SET status = "cancelled", cancelled_at = NOW(), updated_at = NOW() WHERE id = :id')->execute(['id' => $id]);
        }
        flash('Abonnement annulé.', 'warning');
        redirect('/admin/subscriptions');
    }

    public function grantFree(string $userId, string $planId, string $days): never
    {
        AuthMiddleware::requireRole(['admin']);
        (new Subscription())->activatePlan((int) $userId, $planId, 'active', null, null, date('Y-m-d H:i:s', strtotime('+' . (int) $days . ' days')));
        flash('Accès gratuit accordé.', 'success');
        redirect('/admin/subscriptions');
    }

    public function stats(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $stats = [
            'mrr' => (float) (db()->query('SELECT COALESCE(SUM(plans.price),0) FROM subscriptions INNER JOIN plans ON plans.id = subscriptions.plan_id WHERE subscriptions.status = "active"')->fetchColumn() ?: 0),
            'churn_rate' => 0,
            'by_plan' => db()->query('SELECT plans.name, COUNT(*) AS total FROM subscriptions INNER JOIN plans ON plans.id = subscriptions.plan_id GROUP BY plans.name')->fetchAll() ?: [],
        ];
        $this->render('admin/subscriptions/stats', ['title' => 'Statistiques abonnements', 'stats' => $stats], 'admin');
    }
}