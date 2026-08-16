<?php
/** @var string $icon @var string $title @var string $text */
$icon = $icon ?? 'bi-inbox';
$title = $title ?? 'Rien à afficher';
$text = $text ?? '';
$actionUrl = $actionUrl ?? null;
$actionLabel = $actionLabel ?? null;
?>
<div class="text-center text-secondary py-6">
    <i class="bi <?= e($icon) ?> d-block mb-3 opacity-50" style="font-size: 3rem;"></i>
    <h3 class="h5 text-body mb-1"><?= e($title) ?></h3>
    <?php if ($text !== ''): ?><p class="mb-3"><?= e($text) ?></p><?php endif; ?>
    <?php if ($actionUrl && $actionLabel): ?>
        <a class="btn btn-primary" href="<?= e($actionUrl) ?>"><?= e($actionLabel) ?></a>
    <?php endif; ?>
</div>
