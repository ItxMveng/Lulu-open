<section class="py-4">
    <h1 class="mb-4">Messagerie entreprise</h1>
    <?php View::partial('components/chat-window', compact('conversations', 'messages', 'selectedConversationId')); ?>
</section>