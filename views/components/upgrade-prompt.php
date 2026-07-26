<div class="alert alert-warning d-flex justify-content-between align-items-center" role="alert">
    <div>
        <strong>Fonctionnalité limitée.</strong>
        <span><?= e($message ?? 'Passez à un plan supérieur pour débloquer cette action.') ?></span>
    </div>
    <a class="btn btn-sm btn-dark" href="<?= e(url('/pricing')) ?>">Voir les offres</a>
</div>