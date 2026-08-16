<?php
declare(strict_types=1);

final class CvDocument extends Model
{
    public function allForUser(int $userId): array
    {
        $statement = $this->db->prepare('SELECT * FROM cv_documents WHERE user_id = :user_id ORDER BY is_primary DESC, uploaded_at DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll() ?: [];
    }

    public function primaryForUser(int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM cv_documents WHERE user_id = :user_id ORDER BY is_primary DESC, uploaded_at DESC LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function add(int $userId, string $filePath, string $fileName, bool $primary = false): int
    {
        if ($primary) {
            $this->clearPrimary($userId);
        }
        $statement = $this->db->prepare(
            'INSERT INTO cv_documents (user_id, file_path, file_name, uploaded_at, is_primary)
             VALUES (:user_id, :file_path, :file_name, NOW(), :is_primary)'
        );
        $statement->execute([
            'user_id' => $userId,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'is_primary' => $primary ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function find(int $id, int $userId): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM cv_documents WHERE id = :id AND user_id = :user_id LIMIT 1');
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function setPrimary(int $id, int $userId): void
    {
        $this->clearPrimary($userId);
        $statement = $this->db->prepare('UPDATE cv_documents SET is_primary = 1 WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function delete(int $id, int $userId): void
    {
        $statement = $this->db->prepare('DELETE FROM cv_documents WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $id, 'user_id' => $userId]);
    }

    private function clearPrimary(int $userId): void
    {
        $statement = $this->db->prepare('UPDATE cv_documents SET is_primary = 0 WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
    }
}
