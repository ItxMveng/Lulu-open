<?php $flashMessages = get_flash(); ?>
<?php foreach ($flashMessages as $flash): ?>
    <div class="alert alert-<?= e($flash['type'] ?? 'info') ?> alert-dismissible fade show" role="alert">
        <?= e($flash['message'] ?? '') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
<?php endforeach; ?>