<?php
declare(strict_types=1);

final class Message extends Model
{
    public function getConversations(int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT conversations.*, 
                    CASE WHEN conversations.participant_1 = :user_id THEN conversations.participant_2 ELSE conversations.participant_1 END AS other_user_id,
                    users.name AS other_user_name,
                    (SELECT body FROM messages WHERE messages.conversation_id = conversations.id ORDER BY messages.created_at DESC LIMIT 1) AS last_message,
                    (SELECT COUNT(*) FROM messages WHERE messages.conversation_id = conversations.id AND messages.receiver_id = :user_id AND messages.read_at IS NULL) AS unread_count
             FROM conversations
             INNER JOIN users ON users.id = CASE WHEN conversations.participant_1 = :user_id THEN conversations.participant_2 ELSE conversations.participant_1 END
             WHERE conversations.participant_1 = :user_id OR conversations.participant_2 = :user_id
             ORDER BY conversations.last_message_at DESC, conversations.updated_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll() ?: [];
    }

    public function getMessages(int $conversationId, int $userId): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM messages
             WHERE conversation_id = :conversation_id
               AND (sender_id = :user_id OR receiver_id = :user_id)
               AND NOT ((sender_id = :user_id AND deleted_by_sender_at IS NOT NULL) OR (receiver_id = :user_id AND deleted_by_receiver_at IS NOT NULL))
             ORDER BY created_at ASC'
        );
        $statement->execute(['conversation_id' => $conversationId, 'user_id' => $userId]);
        return $statement->fetchAll() ?: [];
    }

    public function getMessagesSince(int $conversationId, int $userId, string $since): array
    {
        $statement = $this->db->prepare(
            'SELECT * FROM messages
             WHERE conversation_id = :conversation_id
               AND (sender_id = :user_id OR receiver_id = :user_id)
               AND created_at > :since
             ORDER BY created_at ASC'
        );
        $statement->execute(['conversation_id' => $conversationId, 'user_id' => $userId, 'since' => $since]);
        return $statement->fetchAll() ?: [];
    }

    public function send(array $data): array
    {
        $conversationId = $this->findOrCreateConversation((int) $data['sender_id'], (int) $data['receiver_id']);
        $statement = $this->db->prepare(
            'INSERT INTO messages (conversation_id, sender_id, receiver_id, body, created_at, updated_at)
             VALUES (:conversation_id, :sender_id, :receiver_id, :body, NOW(), NOW())'
        );
        $statement->execute([
            'conversation_id' => $conversationId,
            'sender_id' => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'body' => $data['body'],
        ]);

        $this->db->prepare('UPDATE conversations SET last_message_at = NOW(), updated_at = NOW() WHERE id = :id')->execute(['id' => $conversationId]);

        return [
            'conversation_id' => $conversationId,
            'message_id' => (int) $this->db->lastInsertId(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    public function markRead(int $conversationId, int $userId): void
    {
        $statement = $this->db->prepare('UPDATE messages SET read_at = NOW() WHERE conversation_id = :conversation_id AND receiver_id = :user_id AND read_at IS NULL');
        $statement->execute(['conversation_id' => $conversationId, 'user_id' => $userId]);
    }

    public function deleteMessage(int $id, int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE messages SET
                deleted_by_sender_at = CASE WHEN sender_id = :user_id THEN NOW() ELSE deleted_by_sender_at END,
                deleted_by_receiver_at = CASE WHEN receiver_id = :user_id THEN NOW() ELSE deleted_by_receiver_at END
             WHERE id = :id'
        );
        $statement->execute(['id' => $id, 'user_id' => $userId]);
    }

    public function deleteConversation(int $id, int $userId): void
    {
        $statement = $this->db->prepare(
            'UPDATE messages SET
                deleted_by_sender_at = CASE WHEN sender_id = :user_id THEN NOW() ELSE deleted_by_sender_at END,
                deleted_by_receiver_at = CASE WHEN receiver_id = :user_id THEN NOW() ELSE deleted_by_receiver_at END
             WHERE conversation_id = :conversation_id'
        );
        $statement->execute(['conversation_id' => $id, 'user_id' => $userId]);
    }

    private function findOrCreateConversation(int $firstUserId, int $secondUserId): int
    {
        $userA = min($firstUserId, $secondUserId);
        $userB = max($firstUserId, $secondUserId);

        $statement = $this->db->prepare('SELECT id FROM conversations WHERE participant_1 = :a AND participant_2 = :b LIMIT 1');
        $statement->execute(['a' => $userA, 'b' => $userB]);
        $existing = $statement->fetch();

        if ($existing) {
            return (int) $existing['id'];
        }

        $insert = $this->db->prepare('INSERT INTO conversations (participant_1, participant_2, last_message_at, created_at, updated_at) VALUES (:a, :b, NOW(), NOW(), NOW())');
        $insert->execute(['a' => $userA, 'b' => $userB]);

        return (int) $this->db->lastInsertId();
    }
}