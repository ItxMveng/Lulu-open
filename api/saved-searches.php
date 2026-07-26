<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';
require_once base_path('includes/ErrorHandler.php');

ErrorHandler::register();
AuthMiddleware::requireAuth();

$model = new SavedSearch();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        json_response(['items' => $model->allForUser((int) current_user_id())]);
    }

    if ($method === 'POST') {
        $payload = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $id = $model->create(
            (int) current_user_id(),
            trim((string) ($payload['name'] ?? 'Recherche sauvegardée')),
            (array) ($payload['filters'] ?? []),
            !empty($payload['alert_enabled'])
        );
        json_response(['success' => true, 'id' => $id], 201);
    }

    if ($method === 'DELETE') {
        parse_str(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY) ?: '', $query);
        $id = (int) ($query['id'] ?? ($_GET['id'] ?? 0));
        $model->delete((int) current_user_id(), $id);
        json_response(['success' => true]);
    }

    json_response(['error' => 'Méthode non autorisée'], 405);
} catch (Throwable $throwable) {
    json_response(['error' => $throwable->getMessage()], 500);
}