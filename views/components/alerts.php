<?php
$flashMessages = get_flash();
if (empty($flashMessages)) {
    return;
}
$icons = [
    'success' => 'bi-check-circle-fill',
    'danger' => 'bi-x-circle-fill',
    'warning' => 'bi-exclamation-triangle-fill',
    'info' => 'bi-info-circle-fill',
];
?>
<div class="flash-stack" id="flashStack">
    <?php foreach ($flashMessages as $flash): $type = (string) ($flash['type'] ?? 'info'); ?>
        <div class="lulu-alert lulu-alert-<?= e($type) ?>" role="alert">
            <i class="bi <?= e($icons[$type] ?? $icons['info']) ?>"></i>
            <div class="pe-3 small"><?= e((string) ($flash['message'] ?? '')) ?></div>
            <button type="button" class="lulu-alert-close" aria-label="Fermer" onclick="this.parentElement.remove()">&times;</button>
        </div>
    <?php endforeach; ?>
</div>
<script>
setTimeout(function () {
    document.querySelectorAll('#flashStack .lulu-alert').forEach(function (el) {
        el.style.transition = 'opacity .4s, transform .4s';
        el.style.opacity = '0'; el.style.transform = 'translateX(16px)';
        setTimeout(function () { el.remove(); }, 400);
    });
}, 5000);
</script>
