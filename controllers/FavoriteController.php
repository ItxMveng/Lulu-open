<?php
declare(strict_types=1);

final class FavoriteController extends Controller
{
    public function toggle(string $targetUserId): never
    {
        AuthMiddleware::requireAuth();
        $userId = (int) current_user_id();
        $targetId = (int) $targetUserId;

        $statement = db()->prepare('SELECT id FROM favorites WHERE user_id = :user_id AND target_user_id = :target_user_id LIMIT 1');
        $statement->execute(['user_id' => $userId, 'target_user_id' => $targetId]);
        $existing = $statement->fetch();

        if ($existing) {
            db()->prepare('DELETE FROM favorites WHERE id = :id')->execute(['id' => $existing['id']]);
            json_response(['added' => false]);
        }

        db()->prepare('INSERT INTO favorites (user_id, target_user_id, created_at) VALUES (:user_id, :target_user_id, NOW())')->execute(['user_id' => $userId, 'target_user_id' => $targetId]);
        json_response(['added' => true]);
    }

    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $statement = db()->prepare('SELECT favorites.*, users.name, users.role FROM favorites INNER JOIN users ON users.id = favorites.target_user_id WHERE favorites.user_id = :user_id ORDER BY favorites.created_at DESC');
        $statement->execute(['user_id' => current_user_id()]);
        $favorites = $statement->fetchAll() ?: [];
        $this->render('pages/favorites', ['title' => 'Mes favoris', 'favorites' => $favorites]);
    }
}