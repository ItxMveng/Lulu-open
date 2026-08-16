<?php
declare(strict_types=1);

final class Subscription extends Model
{
    public function getCurrentByUserId(int $userId): ?array
    {
        $statement = $this->db->prepare(
            'SELECT subscriptions.*, plans.slug AS plan_slug, plans.name AS plan_name, plans.role_target, plans.features, plans.price
             FROM subscriptions
             INNER JOIN plans ON plans.id = subscriptions.plan_id
             WHERE subscriptions.user_id = :user_id
             ORDER BY subscriptions.updated_at DESC, subscriptions.id DESC
             LIMIT 1'
        );
        $statement->execute(['user_id' => $userId]);
        $subscription = $statement->fetch();

        return $subscription ?: null;
    }

    public function findPlanBySlug(string $slug): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM plans WHERE slug = :slug LIMIT 1');
        $statement->execute(['slug' => $slug]);
        $plan = $statement->fetch();

        return $plan ?: null;
    }

    public function activePlans(): array
    {
        return $this->db->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY role_target, price')->fetchAll() ?: [];
    }

    public function activatePlan(int $userId, string $planSlug, string $status = 'active', ?string $stripeCustomerId = null, ?string $stripeSubscriptionId = null, ?string $renewsAt = null): void
    {
        $plan = $this->findPlanBySlug($planSlug);
        if (!$plan) {
            throw new RuntimeException('Plan introuvable.');
        }

        $statement = $this->db->prepare(
            'INSERT INTO subscriptions (user_id, plan_id, stripe_customer_id, stripe_subscription_id, status, starts_at, renews_at, created_at, updated_at)
             VALUES (:user_id, :plan_id, :stripe_customer_id, :stripe_subscription_id, :status, NOW(), :renews_at, NOW(), NOW())'
        );
        $statement->execute([
            'user_id' => $userId,
            'plan_id' => $plan['id'],
            'stripe_customer_id' => $stripeCustomerId,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'status' => $status,
            'renews_at' => $renewsAt,
        ]);

        $updateUser = $this->db->prepare('UPDATE users SET subscription_status = :status, stripe_customer_id = COALESCE(:stripe_customer_id, stripe_customer_id), updated_at = NOW() WHERE id = :id');
        $updateUser->execute([
            'status' => $status,
            'stripe_customer_id' => $stripeCustomerId,
            'id' => $userId,
        ]);
    }

    public function updateByStripeSubscription(string $stripeSubscriptionId, string $status, ?string $planSlug = null, ?string $renewsAt = null): void
    {
        $planId = null;

        if ($planSlug !== null) {
            $plan = $this->findPlanBySlug($planSlug);
            $planId = $plan['id'] ?? null;
        }

        $sql = 'UPDATE subscriptions SET status = :status, renews_at = :renews_at, updated_at = NOW()';
        $params = [
            'status' => $status,
            'renews_at' => $renewsAt,
            'stripe_subscription_id' => $stripeSubscriptionId,
        ];

        if ($planId !== null) {
            $sql .= ', plan_id = :plan_id';
            $params['plan_id'] = $planId;
        }

        $sql .= ' WHERE stripe_subscription_id = :stripe_subscription_id';
        $statement = $this->db->prepare($sql);
        $statement->execute($params);
    }

    public function downgradeToFree(int $userId, string $role): void
    {
        $planSlug = $role === 'entreprise' ? 'entreprise_starter' : 'client_free';
        $this->activatePlan($userId, $planSlug, 'active');
    }

    public function allActive(): array
    {
        $statement = $this->db->query('SELECT subscriptions.*, plans.name AS plan_name, plans.slug AS plan_slug FROM subscriptions INNER JOIN plans ON plans.id = subscriptions.plan_id WHERE subscriptions.status = "active" ORDER BY subscriptions.updated_at DESC');
        return $statement->fetchAll() ?: [];
    }
}