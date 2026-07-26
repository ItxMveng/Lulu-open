<?php
$conversations = $conversations ?? [];
$messages = $messages ?? [];
$selectedConversationId = $selectedConversationId ?? null;
$selectedConversation = $selectedConversation ?? null;
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($conversations as $conversation): ?>
                        <a class="list-group-item list-group-item-action <?= (int) $selectedConversationId === (int) $conversation['id'] ? 'active' : '' ?>" href="<?= e(url('/messages/' . $conversation['id'])) ?>">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong><?= e((string) $conversation['other_user_name']) ?></strong>
                                <?php if ((int) ($conversation['unread_count'] ?? 0) > 0): ?><span class="badge text-bg-primary"><?= e((string) $conversation['unread_count']) ?></span><?php endif; ?>
                            </div>
                            <small class="d-block text-muted"><?= e(mb_strimwidth((string) ($conversation['last_message'] ?? ''), 0, 60, '...')) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body d-flex flex-column" style="min-height: 520px;">
                <div id="chatMessages" class="flex-grow-1 overflow-auto mb-3" style="max-height: 420px;">
                    <?php foreach ($messages as $message): ?>
                        <div class="mb-3 text-<?= (int) $message['sender_id'] === (int) current_user_id() ? 'end' : 'start' ?>">
                            <div class="d-inline-block p-3 rounded <?= (int) $message['sender_id'] === (int) current_user_id() ? 'bg-primary text-white' : 'bg-body-tertiary' ?>">
                                <?= nl2br(e((string) $message['body'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($selectedConversation): ?>
                    <form id="chatForm" class="d-flex gap-2">
                        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="conversation_id" value="<?= e((string) $selectedConversation['id']) ?>">
                        <input type="hidden" name="receiver_id" value="<?= e((string) ($selectedConversation['other_user_id'] ?? 0)) ?>">
                        <input class="form-control" type="text" name="body" placeholder="Écrire un message..." required>
                        <button class="btn btn-primary" type="submit">Envoyer</button>
                    </form>
                <?php else: ?>
                    <?php View::partial('components/upgrade-prompt', ['message' => 'Aucune conversation disponible pour le moment.']); ?>
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
    const wrapper = document.createElement('div');
    wrapper.className = 'mb-3 text-' + (Number(message.sender_id) === <?= (int) current_user_id() ?> ? 'end' : 'start');
    const bubble = document.createElement('div');
    bubble.className = 'd-inline-block p-3 rounded ' + (Number(message.sender_id) === <?= (int) current_user_id() ?> ? 'bg-primary text-white' : 'bg-body-tertiary');
    bubble.textContent = message.body;
    wrapper.appendChild(bubble);
    chatMessages.appendChild(wrapper);
    chatMessages.scrollTop = chatMessages.scrollHeight;
};
chatForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(chatForm);
    const response = await fetch('<?= e(url('/api/messages?action=send')) ?>', { method: 'POST', body: formData });
    const payload = await response.json();
    if (payload.success) {
        renderMessage({ sender_id: <?= (int) current_user_id() ?>, body: formData.get('body') });
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