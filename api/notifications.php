<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_auth()) {
    json_response(['error' => 'Authentification requise'], 401);
}

$model = new Notification();
$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'list');

if ($action === 'list') {
    json_response(['items' => $model->getUnread((int) current_user_id())]);
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['_csrf_token'] ?? null);
    $model->markRead((int) ($_POST['id'] ?? 0));
    json_response(['success' => true]);
}

if ($action === 'mark_all_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($_POST['_csrf_token'] ?? null);
    $model->markAllRead((int) current_user_id());
    json_response(['success' => true]);
}

json_response(['error' => 'Action non prise en charge'], 400);