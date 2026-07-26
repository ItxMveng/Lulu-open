<div class="card shadow-sm h-100 border-0">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h3 class="h5 mb-1"><?= e((string) ($profile['display_name'] ?? $profile['name'] ?? 'Profil')) ?></h3>
                <p class="text-secondary small mb-2"><?= e((string) ($profile['location'] ?? 'Localisation non précisée')) ?></p>
            </div>
            <?php if (isset($profile['ai_score'])): ?>
                <span class="badge text-bg-success">Score IA <?= e((string) $profile['ai_score']) ?></span>
            <?php endif; ?>
        </div>
        <p class="mb-3"><?= e(mb_strimwidth((string) ($profile['bio'] ?? 'Aucune bio disponible.'), 0, 180, '...')) ?></p>
        <a class="btn btn-outline-primary btn-sm" href="<?= e(url('/profile/' . $profile['user_id'])) ?>">Voir le profil</a>
    </div>
</div>