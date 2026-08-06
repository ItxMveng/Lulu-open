<?php
$conversations = $conversations ?? [];
$messages = $messages ?? [];
$selectedConversationId = $selectedConversationId ?? null;
$selectedConversation = $selectedConversation ?? null;
$initialsOf = static function (string $name): string {
    $name = trim($name);
    $ini = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?';
    if (preg_match('/\s(\S)/u', $name, $m)) { $ini .= mb_strtoupper($m[1]); }
    return $ini;
};
?>
<div class="row g-4">
    <!-- Liste des conversations -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body p-0">
                <?php if (empty($conversations)): ?>
                    <div class="p-4 text-center text-secondary">
                        <i class="bi bi-chat-square-dots d-block mb-2 opacity-50" style="font-size: 2rem;"></i>
                        <p class="small mb-0">Aucune conversation.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush chat-list">
                        <?php foreach ($conversations as $conversation): $name = (string) $conversation['other_user_name']; ?>
                            <a class="list-group-item list-group-item-action <?= (int) $selectedConversationId === (int) $conversation['id'] ? 'active' : '' ?>" href="<?= e(url('/messages/' . (int) $conversation['id'])) ?>">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="avatar-sm"><?= e($initialsOf($name)) ?></span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-truncate"><?= e($name) ?></strong>
                                            <?php if ((int) ($conversation['unread_count'] ?? 0) > 0): ?><span class="badge text-bg-primary rounded-pill"><?= e((string) $conversation['unread_count']) ?></span><?php endif; ?>
                                        </div>
                                        <small class="d-block text-secondary text-truncate"><?= e(mb_strimwidth((string) ($conversation['last_message'] ?? ''), 0, 40, '…')) ?></small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Fil de discussion -->
    <div class="col-lg-8">
        <div class="card h-100">
            <?php if ($selectedConversation): ?>
                <div class="card-header bg-white d-flex align-items-center gap-2 py-3">
                    <span class="avatar-sm"><?= e($initialsOf((string) ($selectedConversation['other_user_name'] ?? ''))) ?></span>
                    <strong><?= e((string) ($selectedConversation['other_user_name'] ?? 'Conversation')) ?></strong>
                </div>
            <?php endif; ?>
            <div class="card-body d-flex flex-column" style="min-height: 520px;">
                <div id="chatMessages" class="chat-thread flex-grow-1 overflow-auto mb-3" style="max-height: 430px;">
                    <?php foreach ($messages as $message): $mine = (int) $message['sender_id'] === (int) current_user_id(); ?>
                        <div class="chat-row <?= $mine ? 'me' : 'them' ?>">
                            <div class="chat-bubble"><?= nl2br(e((string) $message['body'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($selectedConversation): ?>
                    <form id="chatForm" class="d-flex gap-2">
                        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="conversation_id" value="<?= e((string) $selectedConversation['id']) ?>">
                        <input type="hidden" name="receiver_id" value="<?= e((string) ($selectedConversation['other_user_id'] ?? 0)) ?>">
                        <input class="form-control chat-input" type="text" name="body" placeholder="Écrire un message…" autocomplete="off" required>
                        <button class="btn btn-primary rounded-circle" type="submit" style="width:44px;height:44px;" aria-label="Envoyer"><i class="bi bi-send"></i></button>
                    </form>
                <?php else: ?>
                    <div class="text-center text-secondary my-auto py-5">
                        <i class="bi bi-chat-dots d-block mb-2 opacity-50" style="font-size: 2.5rem;"></i>
                        <p class="mb-0">Sélectionnez une conversation pour commencer à discuter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($selectedConversation): ?>
<script>
const chatForm = document.getElementById('chatForm');
const chatMessages = document.getElementById('chatMessages');
const renderMessage = (message) => {
    const mine = Number(message.sender_id) === <?= (int) current_user_id() ?>;
    const row = document.createElement('div');
    row.className = 'chat-row ' + (mine ? 'me' : 'them');
    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.textContent = message.body;
    row.appendChild(bubble);
    chatMessages.appendChild(row);
    chatMessages.scrollTop = chatMessages.scrollHeight;
};
chatForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(chatForm);
    const body = formData.get('body');
    if (!body || !body.trim()) return;
    const response = await fetch('<?= e(url('/api/messages?action=send')) ?>', { method: 'POST', body: formData });
    const payload = await response.json();
    if (payload.success) {
        renderMessage({ sender_id: <?= (int) current_user_id() ?>, body });
        chatForm.reset();
    } else if (payload.upgrade_required) {
        window.location.href = '<?= e(url('/pricing')) ?>';
    }
});
setInterval(async () => {
    const response = await fetch('<?= e(url('/api/messages?action=poll&conversation_id=' . (int) $selectedConversation['id'])) ?>');
    const payload = await response.json();
    (payload.items || []).forEach(renderMessage);
}, 3000);
chatMessages.scrollTop = chatMessages.scrollHeight;
</script>
<?php endif; ?>
