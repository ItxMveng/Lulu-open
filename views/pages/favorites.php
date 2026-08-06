<?php
$favorites = $favorites ?? [];
$roleLabels = ['client' => 'Candidat', 'entreprise' => 'Entreprise'];
$initialsOf = static function (string $name): string {
    $name = trim($name);
    $ini = $name !== '' ? mb_strtoupper(mb_substr($name, 0, 1)) : '?';
    if (preg_match('/\s(\S)/u', $name, $m)) { $ini .= mb_strtoupper($m[1]); }
    return $ini;
};
?>
<section class="py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Mes favoris</h1>
        <p class="text-secondary mb-0"><?= count($favorites) ?> profil(s) sauvegardé(s)</p>
    </div>

    <?php if (empty($favorites)): ?>
        <?php View::partial('components/empty-state', [
            'icon' => 'bi-bookmark-heart',
            'title' => 'Aucun favori pour le moment',
            'text' => 'Sauvegardez des profils pour les retrouver ici.',
            'actionUrl' => url('/search'),
            'actionLabel' => 'Explorer les profils',
        ]); ?>
    <?php else: ?>
        <div class="row g-3 g-lg-4">
            <?php foreach ($favorites as $favorite): $name = (string) ($favorite['name'] ?? ''); ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card card-hover h-100">
                        <div class="card-body p-4 d-flex align-items-center gap-3">
                            <span class="avatar-circle"><?= e($initialsOf($name)) ?></span>
                            <div class="flex-grow-1 min-w-0">
                                <h2 class="h6 mb-1 text-truncate"><?= e($name) ?></h2>
                                <span class="badge badge-soft-primary"><?= e($roleLabels[(string) ($favorite['role'] ?? '')] ?? (string) ($favorite['role'] ?? '')) ?></span>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/profile/' . (int) ($favorite['target_user_id'] ?? 0))) ?>">Voir</a>
                                <form method="post" action="<?= e(url('/favorites/' . (int) ($favorite['target_user_id'] ?? 0) . '/toggle')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger w-100" type="submit" aria-label="Retirer des favoris"><i class="bi bi-bookmark-x"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
