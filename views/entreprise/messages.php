<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1"><i class="bi bi-chat-dots me-2"></i>Messagerie</h1>
        <p class="text-secondary mb-0">Échangez avec les candidats et les talents.</p>
    </div>
    <?php View::partial('components/chat-window', compact('conversations', 'messages', 'selectedConversationId', 'selectedConversation')); ?>
</section>
