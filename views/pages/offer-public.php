<?php
$offer = $offer ?? [];
$companyName = trim((string) ($companyName ?? ''));
$type = (string) ($offer['type'] ?? 'emploi');
$typeLabels = ['emploi' => 'Emploi', 'mission' => 'Mission', 'stage' => 'Stage'];
$location = trim((string) ($offer['location'] ?? ''));
$canonical = offer_url($offer);

// Dates ISO pour le schema.
$datePosted = !empty($offer['created_at']) ? date('Y-m-d', strtotime((string) $offer['created_at'])) : null;
$validThrough = !empty($offer['expires_at']) ? date('c', strtotime((string) $offer['expires_at'])) : null;

// employmentType Schema.org à partir du type de contrat.
$empMap = ['CDI' => 'FULL_TIME', 'CDD' => 'TEMPORARY', 'Freelance' => 'CONTRACTOR', 'Alternance' => 'INTERN', 'Stage' => 'INTERN', 'Intérim' => 'TEMPORARY'];
$employmentType = $empMap[(string) ($offer['contract_type'] ?? '')]
    ?? ($type === 'stage' ? 'INTERN' : ($type === 'mission' ? 'CONTRACTOR' : 'FULL_TIME'));

// --- Données structurées JobPosting (Google for Jobs) ---
$jobLd = [
    '@context' => 'https://schema.org',
    '@type' => 'JobPosting',
    'title' => (string) ($offer['title'] ?? 'Offre'),
    'description' => (string) ($offer['description'] ?? ''),
    'employmentType' => $employmentType,
    'hiringOrganization' => ['@type' => 'Organization', 'name' => $companyName !== '' ? $companyName : APP_NAME],
    'jobLocation' => ['@type' => 'Place', 'address' => ['@type' => 'PostalAddress', 'addressLocality' => $location !== '' ? $location : 'N/A']],
    'url' => $canonical,
    'directApply' => true,
];
if ($datePosted) { $jobLd['datePosted'] = $datePosted; }
if ($validThrough) { $jobLd['validThrough'] = $validThrough; }
if (!empty($offer['remote_ok'])) { $jobLd['jobLocationType'] = 'TELECOMMUTE'; }
if (!empty($offer['salary_min']) || !empty($offer['salary_max'])) {
    $jobLd['baseSalary'] = [
        '@type' => 'MonetaryAmount', 'currency' => 'EUR',
        'value' => array_filter([
            '@type' => 'QuantitativeValue',
            'minValue' => !empty($offer['salary_min']) ? (float) $offer['salary_min'] : null,
            'maxValue' => !empty($offer['salary_max']) ? (float) $offer['salary_max'] : null,
            'unitText' => 'MONTH',
        ], static fn ($v) => $v !== null),
    ];
}
$breadcrumbLd = [
    '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => t('Accueil'), 'item' => APP_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => t('Offres'), 'item' => APP_URL . '/search?tab=offres'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => (string) ($offer['title'] ?? 'Offre'), 'item' => $canonical],
    ],
];
?>
<script type="application/ld+json"><?= json_encode($jobLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<section class="py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>"><?= t('Accueil') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('/search?tab=offres')) ?>"><?= t('Offres') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e((string) ($offer['title'] ?? 'Offre')) ?></li>
        </ol>
    </nav>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4 p-lg-5">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge badge-soft-primary"><i class="bi bi-briefcase me-1"></i><?= e(t($typeLabels[$type] ?? ucfirst($type))) ?></span>
                        <?php if (!empty($offer['contract_type'])): ?><span class="badge badge-soft-primary"><?= e((string) $offer['contract_type']) ?></span><?php endif; ?>
                        <?php if (!empty($offer['remote_ok'])): ?><span class="badge badge-soft-success"><i class="bi bi-house-door me-1"></i><?= t('Télétravail') ?></span><?php endif; ?>
                    </div>
                    <h1 class="h3 mb-2"><?= e((string) ($offer['title'] ?? 'Offre')) ?></h1>
                    <?php if ($companyName !== ''): ?>
                        <p class="text-primary fw-semibold mb-3"><i class="bi bi-building me-1"></i><?= e($companyName) ?> <span class="badge badge-soft-success ms-1"><i class="bi bi-patch-check-fill me-1"></i><?= t('Vérifiée') ?></span></p>
                    <?php endif; ?>
                    <div class="text-secondary mb-4 d-flex flex-wrap gap-3">
                        <span><i class="bi bi-geo-alt me-1"></i><?= e($location !== '' ? $location : t('Non précisé')) ?></span>
                        <?php if (!empty($offer['salary_min']) || !empty($offer['salary_max'])): ?>
                            <span><i class="bi bi-cash-coin me-1"></i><?= e(money((float) ($offer['salary_min'] ?? 0))) ?><?= !empty($offer['salary_max']) ? ' – ' . e(money((float) $offer['salary_max'])) : '' ?></span>
                        <?php endif; ?>
                        <?php if ($datePosted): ?><span><i class="bi bi-calendar3 me-1"></i><?= t('Publiée le') ?> <?= e(date('d/m/Y', strtotime((string) $offer['created_at']))) ?></span><?php endif; ?>
                    </div>
                    <hr>
                    <h2 class="h6 text-uppercase text-secondary mb-3" style="letter-spacing:.05em;"><?= t('Description du poste') ?></h2>
                    <div class="offer-body"><?= nl2br(e((string) ($offer['description'] ?? ''))) ?></div>
                </div>
            </div>
        </div>

        <!-- Colonne action -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 88px;">
                <div class="card-body p-4 text-center">
                    <?php if (!is_auth()): ?>
                        <p class="text-secondary small mb-3"><?= t('Connectez-vous en tant que candidat pour postuler à cette offre.') ?></p>
                        <a class="btn btn-primary w-100 mb-2" href="<?= e(url('/login')) ?>"><?= t('Se connecter') ?></a>
                        <a class="btn btn-outline-secondary w-100" href="<?= e(url('/register')) ?>"><?= t('Créer un compte') ?></a>
                    <?php elseif (current_role() === 'client'): ?>
                        <i class="bi bi-send-check text-primary d-block mb-2" style="font-size: 2rem;"></i>
                        <p class="text-secondary small mb-3"><?= t('Envoyez votre candidature avec votre CV et une lettre de motivation.') ?></p>
                        <a class="btn btn-primary btn-lg w-100" href="<?= e(url('/offres/' . (int) ($offer['id'] ?? 0) . '/postuler')) ?>"><?= t('Postuler maintenant') ?></a>
                    <?php elseif (current_role() === 'entreprise'): ?>
                        <p class="text-secondary small mb-0"><i class="bi bi-info-circle me-1"></i><?= t('Vous consultez cette offre en tant qu\'entreprise.') ?></p>
                    <?php else: ?>
                        <p class="text-secondary small mb-0"><?= t('Consultation administrateur.') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
