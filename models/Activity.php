<?php
declare(strict_types=1);

final class Activity extends Model
{
    public function log(?int $userId, string $action, array $metadata = []): void
    {
        $statement = $this->db->prepare('INSERT INTO activity_logs (user_id, action, metadata, created_at) VALUES (:user_id, :action, :metadata, NOW())');
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function getRecent(?int $userId, int $limit = 10): array
    {
        if ($userId === null) {
            $statement = $this->db->prepare('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT :limit');
            $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
            $statement->execute();
            return $statement->fetchAll() ?: [];
        }

        $statement = $this->db->prepare('SELECT * FROM activity_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit');
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll() ?: [];
    }

    public function getStats(?int $userId = null): array
    {
        if ($userId === null) {
            return [
                'users_total' => (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'subscriptions_active' => (int) db()->query('SELECT COUNT(*) FROM subscriptions WHERE status = "active"')->fetchColumn(),
                'messages_total' => (int) db()->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
                'offers_active' => (int) db()->query('SELECT COUNT(*) FROM offers WHERE status = "active"')->fetchColumn(),
            ];
        }

        return [
            'activity_total' => (int) $this->db->query('SELECT COUNT(*) FROM activity_logs WHERE user_id = ' . (int) $userId)->fetchColumn(),
            'offers_total' => (int) $this->db->query('SELECT COUNT(*) FROM offers WHERE entreprise_id = ' . (int) $userId)->fetchColumn(),
            'messages_total' => (int) $this->db->query('SELECT COUNT(*) FROM messages WHERE sender_id = ' . (int) $userId . ' OR receiver_id = ' . (int) $userId)->fetchColumn(),
        ];
    }
}