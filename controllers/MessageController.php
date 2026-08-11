<?php
declare(strict_types=1);

final class MessageController extends Controller
{
    private Message $messages;
    private Notification $notifications;

    public function __construct()
    {
        $this->messages = new Message();
        $this->notifications = new Notification();
    }

    public function index(): void
    {
        AuthMiddleware::requireAuth();
        $conversations = $this->messages->getConversations((int) current_user_id());
        $selectedConversation = $conversations[0] ?? null;
        $messages = $selectedConversation ? $this->messages->getMessages((int) $selectedConversation['id'], (int) current_user_id()) : [];
        $view = current_role() === 'entreprise' ? 'entreprise/messages' : 'client/messages';

        $this->render($view, [
            'title' => 'Messagerie',
            'conversations' => $conversations,
            'messages' => $messages,
            'selectedConversationId' => $selectedConversation['id'] ?? null,
            'selectedConversation' => $selectedConversation,
        ]);
    }

    /** Ouvre (ou crée) une conversation avec un utilisateur et redirige dessus. */
    public function start(string $userId): never
    {
        AuthMiddleware::requireAuth();
        $other = (int) $userId;
        if ($other <= 0 || $other === (int) current_user_id()) {
            redirect('/messages');
        }
        $conversationId = $this->messages->openConversation((int) current_user_id(), $other);
        redirect('/messages/' . $conversationId);
    }

    public function conversation(string $id): void
    {
        AuthMiddleware::requireAuth();
        $conversations = $this->messages->getConversations((int) current_user_id());
        $selectedConversation = null;
        foreach ($conversations as $conversation) {
            if ((int) $conversation['id'] === (int) $id) {
                $selectedConversation = $conversation;
                break;
            }
        }
        $messages = $this->messages->getMessages((int) $id, (int) current_user_id());
        $view = current_role() === 'entreprise' ? 'entreprise/messages' : 'client/messages';

        $this->render($view, [
            'title' => 'Messagerie',
            'conversations' => $conversations,
            'messages' => $messages,
            'selectedConversationId' => (int) $id,
            'selectedConversation' => $selectedConversation,
        ]);
    }

    public function sendMessage(): never
    {
        AuthMiddleware::requireAuth();
        if (!SubscriptionHelper::canSendMessage((int) current_user_id())) {
            json_response(['success' => false, 'upgrade_required' => true], 403);
        }

        $payload = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
        verify_csrf($payload['_csrf_token'] ?? null);

        $result = $this->messages->send([
            'sender_id' => (int) current_user_id(),
            'receiver_id' => (int) ($payload['receiver_id'] ?? 0),
            'body' => trim((string) ($payload['body'] ?? '')),
        ]);

        $this->notifications->create((int) ($payload['receiver_id'] ?? 0), 'message_received', ['conversation_id' => $result['conversation_id']]);
        json_response(['success' => true] + $result);
    }

    public function deleteMessage(string $id): never
    {
        AuthMiddleware::requireAuth();
        verify_csrf();
        $this->messages->deleteMessage((int) $id, (int) current_user_id());
        json_response(['success' => true]);
    }

    public function deleteConversation(string $id): never
    {
        AuthMiddleware::requireAuth();
        verify_csrf();
        $this->messages->deleteConversation((int) $id, (int) current_user_id());
        json_response(['success' => true]);
    }
}