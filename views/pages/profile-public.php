<?php
$profile = $profile ?? [];
$categories = json_decode((string) ($profile['categories'] ?? '[]'), true) ?: [];
$skills = json_decode((string) ($profile['skills'] ?? '[]'), true) ?: [];
?>
<section class="py-4">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4 p-lg-5">
            <div class="row g-4 align-items-start">
                <div class="col-lg-3">
                    <?php if (!empty($profile['photo_path'])): ?>
                        <img class="img-fluid rounded" src="<?= e(url('/' . $profile['photo_path'])) ?>" alt="Photo de profil">
                    <?php else: ?>
                        <div class="bg-body-tertiary rounded p-5 text-center text-muted">Aucune photo</div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-9">
                    <h1 class="mb-2"><?= e((string) ($profile['display_name'] ?? $profile['name'] ?? 'Profil')) ?></h1>
                    <p class="text-secondary mb-1">Type de profil: <?= e((string) ($profile['type'] ?? 'services')) ?></p>
                    <p class="text-secondary mb-3">Localisation: <?= e((string) ($profile['location'] ?? 'Non renseignée')) ?></p>
                    <p><?= nl2br(e((string) ($profile['bio'] ?? 'Aucune description disponible.'))) ?></p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <?php foreach ($categories as $category): ?><span class="badge text-bg-light border"><?= e((string) $category) ?></span><?php endforeach; ?>
                        <?php foreach ($skills as $skill): ?><span class="badge text-bg-primary-subtle text-primary"><?= e((string) $skill) ?></span><?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>