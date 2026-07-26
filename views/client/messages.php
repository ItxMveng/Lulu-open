<section class="py-4">
    <h1 class="mb-4">Messagerie client</h1>
    <?php View::partial('components/chat-window', compact('conversations', 'messages', 'selectedConversationId')); ?>
</section>