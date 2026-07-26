<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_auth()) {
    json_response(['error' => 'Authentification requise'], 401);
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'GET') {
    $statement = db()->prepare('SELECT target_user_id FROM favorites WHERE user_id = :user_id');
    $statement->execute(['user_id' => current_user_id()]);
    json_response(['items' => $statement->fetchAll(PDO::FETCH_COLUMN)]);
}

verify_csrf($_POST['_csrf_token'] ?? null);
$targetId = (int) ($_POST['target_user_id'] ?? 0);
$controller = new FavoriteController();
$controller->toggle((string) $targetId);