<?php
declare(strict_types=1);

final class Notification extends Model
{
    public function getUnread(int $userId): array
    {
        $statement = $this->db->prepare('SELECT * FROM notifications WHERE user_id = :user_id AND read_at IS NULL ORDER BY created_at DESC LIMIT 20');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll() ?: [];
    }

    public function markRead(int $id): void
    {
        $statement = $this->db->prepare('UPDATE notifications SET read_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function markAllRead(int $userId): void
    {
        $statement = $this->db->prepare('UPDATE notifications SET read_at = NOW() WHERE user_id = :user_id AND read_at IS NULL');
        $statement->execute(['user_id' => $userId]);
    }

    public function create(int $userId, string $type, array $data): void
    {
        $statement = $this->db->prepare('INSERT INTO notifications (user_id, type, data, created_at) VALUES (:user_id, :type, :data, NOW())');
        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    public function deleteOld(int $daysOld): void
    {
        $statement = $this->db->prepare('DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)');
        $statement->bindValue(':days', $daysOld, PDO::PARAM_INT);
        $statement->execute();
    }
}