<?php
declare(strict_types=1);

final class User extends Model
{
    public function findById(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        $user = $statement->fetch();

        return $user ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->db->prepare(
            'INSERT INTO users (name, email, password_hash, role, status, subscription_status, created_at, updated_at)
             VALUES (:name, :email, :password_hash, :role, :status, :subscription_status, NOW(), NOW())'
        );

        $statement->execute([
            'name' => $data['name'],
            'email' => strtolower(trim((string) $data['email'])),
            'password_hash' => $data['password_hash'],
            'role' => $data['role'],
            'status' => $data['status'] ?? 'active',
            'subscription_status' => $data['subscription_status'] ?? 'inactive',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createProfileForUser(int $userId, string $displayName, string $type = 'services'): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO profiles (user_id, type, display_name, created_at, updated_at)
             VALUES (:user_id, :type, :display_name, NOW(), NOW())'
        );

        $statement->execute([
            'user_id' => $userId,
            'type' => $type,
            'display_name' => $displayName,
        ]);
    }

    public function setVerificationStatus(int $userId, ?string $status): void
    {
        $statement = $this->db->prepare('UPDATE users SET verification_status = :s, updated_at = NOW() WHERE id = :id');
        $statement->execute(['s' => $status, 'id' => $userId]);
    }

    public function assignDefaultSubscription(int $userId, string $role): void
    {
        $planSlug = $role === 'entreprise' ? 'entreprise_starter' : 'client_free';
        $planStatement = $this->db->prepare('SELECT id FROM plans WHERE slug = :slug LIMIT 1');
        $planStatement->execute(['slug' => $planSlug]);
        $plan = $planStatement->fetch();

        if (!$plan) {
            return;
        }

        $statement = $this->db->prepare(
            'INSERT INTO subscriptions (user_id, plan_id, status, starts_at, created_at, updated_at)
             VALUES (:user_id, :plan_id, :status, NOW(), NOW(), NOW())'
        );

        $statement->execute([
            'user_id' => $userId,
            'plan_id' => $plan['id'],
            'status' => 'active',
        ]);

        $update = $this->db->prepare('UPDATE users SET subscription_status = :status WHERE id = :id');
        $update->execute(['status' => 'active', 'id' => $userId]);
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $statement = $this->db->prepare('UPDATE users SET name = :name, updated_at = NOW() WHERE id = :id');
        return $statement->execute([
            'id' => $userId,
            'name' => $data['name'] ?? '',
        ]);
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $statement = $this->db->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        return $statement->execute([
            'id' => $userId,
            'password_hash' => $passwordHash,
        ]);
    }

    public function delete(int $userId): bool
    {
        $statement = $this->db->prepare('UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id');
        return $statement->execute([
            'id' => $userId,
            'status' => 'deleted',
        ]);
    }

    public function updateLoginTimestamp(int $userId): void
    {
        $statement = $this->db->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $userId]);
    }

    public function createPasswordReset(int $userId, string $token, DateTimeInterface $expiresAt): void
    {
        $cleanup = $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = :user_id AND used_at IS NULL');
        $cleanup->execute(['user_id' => $userId]);

        $statement = $this->db->prepare(
            'INSERT INTO password_resets (user_id, token, expires_at, created_at)
             VALUES (:user_id, :token, :expires_at, NOW())'
        );

        $statement->execute([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findPasswordResetByToken(string $token): ?array
    {
        $statement = $this->db->prepare(
            'SELECT password_resets.*, users.email, users.name, users.id AS linked_user_id
             FROM password_resets
             INNER JOIN users ON users.id = password_resets.user_id
             WHERE password_resets.token = :token
             LIMIT 1'
        );
        $statement->execute(['token' => $token]);
        $reset = $statement->fetch();

        return $reset ?: null;
    }

    public function markPasswordResetUsed(string $token): void
    {
        $statement = $this->db->prepare('UPDATE password_resets SET used_at = NOW() WHERE token = :token');
        $statement->execute(['token' => $token]);
    }
}