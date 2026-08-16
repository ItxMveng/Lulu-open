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
                        <span class="display-6 fw-bold"><?= e(money((float) $price)) ?></span>
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
        <p class="lead mx-auto mb-3" style="max-width: 44ch;">Commencez gratuitement, évoluez quand vous en avez besoin.</p>
        <?php $cur = CurrencyService::info(); ?>
        <div class="dropdown d-inline-block">
            <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" type="button">
                <i class="bi bi-globe2 me-1"></i>Devise : <?= e($cur['code']) ?> (<?= e($cur['symbol']) ?>)
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="max-height:280px;overflow:auto;">
                <?php foreach (CurrencyService::all() as $code => $c): ?>
                    <li><a class="dropdown-item <?= $code === $cur['code'] ? 'active' : '' ?>" href="<?= e(url('/devise/' . $code)) ?>"><?= e($code) ?> — <?= e($c[1]) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="text-secondary small mt-2"><i class="bi bi-info-circle me-1"></i>Devise détectée selon votre position. Les tarifs sont convertis depuis l'euro (à titre indicatif).</div>
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

<section class="section reveal">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <h2 class="h3 mb-2"><?= t('Questions fréquentes') ?></h2>
                    <p class="text-secondary mb-0"><?= t('Tout ce que vous devez savoir avant de commencer.') ?></p>
                </div>
                <div class="accordion" id="pricingFaq">
                    <?php
                    $pfaq = [
                        ['Puis-je changer de plan à tout moment ?', 'Oui, vous pouvez passer à un plan supérieur ou revenir au plan gratuit quand vous le souhaitez.'],
                        ['Comment sont affichés les prix ?', 'Les prix sont convertis automatiquement dans la devise de votre pays (FCFA, Naira, etc.) à titre indicatif.'],
                        ['Y a-t-il un engagement ?', 'Non, aucun engagement. Les abonnements sont mensuels et sans engagement de durée.'],
                        ['Le plan gratuit est-il vraiment gratuit ?', 'Oui. Vous pouvez créer un profil, postuler et échanger sans payer. Les plans payants ajoutent des fonctionnalités avancées.'],
                    ];
                    foreach ($pfaq as $i => [$q, $a]): ?>
                        <div class="accordion-item border-0 mb-2 rounded-3 overflow-hidden">
                            <h3 class="accordion-header"><button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#pf<?= $i ?>"><?= e($q) ?></button></h3>
                            <div id="pf<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#pricingFaq"><div class="accordion-body text-secondary"><?= e($a) ?></div></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
