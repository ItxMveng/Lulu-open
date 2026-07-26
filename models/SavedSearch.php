<?php
declare(strict_types=1);

final class SavedSearch extends Model
{
    public function allForUser(int $userId): array
    {
        $statement = $this->db->prepare('SELECT * FROM saved_searches WHERE user_id = :user_id ORDER BY created_at DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll() ?: [];
    }

    public function create(int $userId, string $name, array $filters, bool $alertEnabled): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO saved_searches (user_id, name, filters, alert_enabled, created_at, updated_at)
             VALUES (:user_id, :name, :filters, :alert_enabled, NOW(), NOW())'
        );
        $statement->execute([
            'user_id' => $userId,
            'name' => $name,
            'filters' => json_encode($filters, JSON_UNESCAPED_UNICODE),
            'alert_enabled' => $alertEnabled ? 1 : 0,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $userId, int $id): bool
    {
        $statement = $this->db->prepare('DELETE FROM saved_searches WHERE id = :id AND user_id = :user_id');
        return $statement->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function allAlertEnabled(): array
    {
        $statement = $this->db->query('SELECT * FROM saved_searches WHERE alert_enabled = 1');
        return $statement->fetchAll() ?: [];
    }
}