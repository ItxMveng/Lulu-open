<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json; charset=UTF-8');

if (!is_auth()) {
    json_response(['error' => 'Authentification requise'], 401);
}

$messageModel = new Message();
$notificationModel = new Notification();
$action = (string) ($_GET['action'] ?? $_POST['action'] ?? '');
$payload = $_POST;

if ($action === 'send' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($payload['_csrf_token'] ?? null);
    if (!SubscriptionHelper::canSendMessage((int) current_user_id())) {
        json_response(['success' => false, 'upgrade_required' => true], 403);
    }

    $result = $messageModel->send([
        'sender_id' => (int) current_user_id(),
        'receiver_id' => (int) ($payload['receiver_id'] ?? 0),
        'body' => trim((string) ($payload['body'] ?? '')),
    ]);
    $notificationModel->create((int) ($payload['receiver_id'] ?? 0), 'message_received', ['conversation_id' => $result['conversation_id']]);
    json_response(['success' => true] + $result);
}

if ($action === 'poll') {
    $conversationId = (int) ($_GET['conversation_id'] ?? 0);
    $since = (string) ($_GET['since'] ?? date('Y-m-d H:i:s', strtotime('-1 day')));
    $items = $messageModel->getMessagesSince($conversationId, (int) current_user_id(), $since);
    json_response(['items' => $items]);
}

if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($payload['_csrf_token'] ?? null);
    $messageModel->markRead((int) ($payload['conversation_id'] ?? 0), (int) current_user_id());
    json_response(['success' => true]);
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf($payload['_csrf_token'] ?? null);
    $messageModel->deleteMessage((int) ($payload['id'] ?? 0), (int) current_user_id());
    json_response(['success' => true]);
}

json_response(['error' => 'Action non prise en charge'], 400);