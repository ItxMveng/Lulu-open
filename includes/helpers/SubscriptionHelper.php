<?php
declare(strict_types=1);

final class SubscriptionHelper
{
    public static function getActivePlan(int $userId): array
    {
        $subscriptionModel = new Subscription();
        $subscription = $subscriptionModel->getCurrentByUserId($userId);

        if ($subscription) {
            return $subscription;
        }

        $user = (new User())->findById($userId);
        $fallbackSlug = ($user['role'] ?? 'client') === 'entreprise' ? 'entreprise_starter' : 'client_free';
        $plan = $subscriptionModel->findPlanBySlug($fallbackSlug);

        return [
            'plan_slug' => $plan['slug'] ?? $fallbackSlug,
            'plan_name' => $plan['name'] ?? $fallbackSlug,
            'status' => 'active',
            'price' => $plan['price'] ?? 0,
            'features' => $plan['features'] ?? '[]',
        ];
    }

    public static function canSendMessage(int $userId): bool
    {
        $plan = self::getActivePlan($userId);
        $slug = $plan['plan_slug'] ?? '';
        $user = (new User())->findById($userId);
        $role = $user['role'] ?? 'client';

        if ($role === 'client') {
            return $slug === 'client_pro';
        }

        if ($slug === 'entreprise_starter') {
            $statement = db()->prepare('SELECT COUNT(*) FROM messages WHERE sender_id = :sender_id AND DATE(created_at) = CURDATE()');
            $statement->execute(['sender_id' => $userId]);
            return (int) $statement->fetchColumn() < 5;
        }

        return true;
    }

    public static function canAccessAI(int $userId): bool
    {
        $slug = self::getActivePlan($userId)['plan_slug'] ?? '';
        return in_array($slug, ['entreprise_pro', 'entreprise_business'], true);
    }

    public static function canPublishOffer(int $userId): bool
    {
        $slug = self::getActivePlan($userId)['plan_slug'] ?? '';
        if ($slug !== 'entreprise_starter') {
            return true;
        }

        $statement = db()->prepare('SELECT COUNT(*) FROM offers WHERE entreprise_id = :entreprise_id AND status = "active"');
        $statement->execute(['entreprise_id' => $userId]);
        return (int) $statement->fetchColumn() < 1;
    }
}