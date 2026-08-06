<?php
$clientPlans = $clientPlans ?? [];
$entreprisePlans = $entreprisePlans ?? [];

$renderPlan = static function (array $plan): string {
    $name = (string) ($plan['name'] ?? 'Plan');
    $price = (float) ($plan['price'] ?? 0);
    $slug = (string) ($plan['slug'] ?? '');
    $featured = str_contains($slug, 'pro');
    $features = [];
    if (!empty($plan['features'])) {
        $decoded = json_decode((string) $plan['features'], true);
        if (is_array($decoded)) { $features = $decoded; }
    }

    $ctaUrl = is_auth()
        ? ($price > 0 ? url('/abonnement/checkout/' . (int) ($plan['id'] ?? 0)) : url('/abonnement'))
        : url('/register');
    $ctaLabel = $price > 0 ? 'Choisir ce plan' : 'Commencer gratuitement';

    ob_start(); ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 <?= $featured ? 'plan-featured' : '' ?>">
            <div class="card-body p-4 d-flex flex-column">
                <?php if ($featured): ?><span class="badge badge-soft-primary align-self-start mb-2"><i class="bi bi-star-fill me-1"></i>Populaire</span><?php endif; ?>
                <h3 class="h5 mb-1"><?= e($name) ?></h3>
                <div class="mb-3">
                    <?php if ($price > 0): ?>
                        <span class="display-6 fw-bold"><?= e(rtrim(rtrim(number_format($price, 2, ',', ' '), '0'), ',')) ?> €</span>
                        <span class="text-secondary">/mois</span>
                    <?php else: ?>
                        <span class="display-6 fw-bold">Gratuit</span>
                    <?php endif; ?>
                </div>
                <ul class="list-unstyled d-flex flex-column gap-2 flex-grow-1 mb-4">
                    <?php foreach ($features as $feature): ?>
                        <li class="d-flex gap-2"><i class="bi bi-check-circle-fill text-success mt-1"></i><span class="small"><?= e((string) $feature) ?></span></li>
                    <?php endforeach; ?>
                </ul>
                <a class="btn <?= $featured ? 'btn-primary' : 'btn-outline-primary' ?> w-100" href="<?= e($ctaUrl) ?>"><?= e($ctaLabel) ?></a>
            </div>
        </div>
    </div>
    <?php return (string) ob_get_clean();
};
?>

<section class="hero">
    <div class="container py-5 text-center">
        <span class="hero-eyebrow mb-3"><i class="bi bi-gem"></i> Tarifs simples et transparents</span>
        <h1 class="fw-bold mb-2">Choisissez le plan qui vous ressemble</h1>
        <p class="lead mx-auto mb-0" style="max-width: 44ch;">Commencez gratuitement, évoluez quand vous en avez besoin.</p>
    </div>
</section>

<section class="section pt-5">
    <div class="container">
        <div class="d-flex align-items-center gap-2 mb-4">
            <span class="category-icon"><i class="bi bi-person-badge"></i></span>
            <div>
                <h2 class="h4 mb-0">Candidats & talents</h2>
                <p class="text-secondary small mb-0">Pour postuler et proposer vos services.</p>
            </div>
        </div>
        <div class="row g-4 mb-5">
            <?php foreach ($clientPlans as $plan) { echo $renderPlan($plan); } ?>
            <?php if (empty($clientPlans)): ?><p class="text-secondary">Aucun plan disponible.</p><?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2 mb-4">
            <span class="category-icon"><i class="bi bi-building"></i></span>
            <div>
                <h2 class="h4 mb-0">Entreprises & recruteurs</h2>
                <p class="text-secondary small mb-0">Pour publier des offres et recruter.</p>
            </div>
        </div>
        <div class="row g-4">
            <?php foreach ($entreprisePlans as $plan) { echo $renderPlan($plan); } ?>
            <?php if (empty($entreprisePlans)): ?><p class="text-secondary">Aucun plan disponible.</p><?php endif; ?>
        </div>
    </div>
</section>
