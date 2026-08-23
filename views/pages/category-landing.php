<?php
$category = $category ?? [];
$name = (string) ($category['name'] ?? '');
$icon = (string) ($category['icon'] ?? 'bi-grid');
$slug = (string) ($category['slug'] ?? '');
$talentItems = $talents['items'] ?? [];
$talentTotal = (int) ($talents['total'] ?? 0);
$offers = $offers ?? [];
$allCategories = $allCategories ?? [];
$canonical = APP_URL . '/categorie/' . rawurlencode($slug);

$breadcrumbLd = [
    '@context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => t('Accueil'), 'item' => APP_URL . '/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => t('Domaines'), 'item' => APP_URL . '/categories'],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $canonical],
    ],
];
$collectionLd = [
    '@context' => 'https://schema.org', '@type' => 'CollectionPage',
    'name' => $name . ' — ' . APP_NAME, 'url' => $canonical,
    'about' => $name,
];
?>
<script type="application/ld+json"><?= json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<script type="application/ld+json"><?= json_encode($collectionLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<div class="container py-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>"><?= t('Accueil') ?></a></li>
            <li class="breadcrumb-item"><a href="<?= e(url('/categories')) ?>"><?= t('Domaines') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e(t($name)) ?></li>
        </ol>
    </nav>

    <header class="mb-5">
        <span class="category-icon mb-3"><i class="bi <?= e($icon) ?> text-primary"></i></span>
        <h1 class="fw-bold mb-2"><?= e(t($name)) ?> <span class="text-secondary fw-normal">— <?= t('talents & opportunités') ?></span></h1>
        <p class="lead text-secondary mb-0" style="max-width: 60ch;">
            <?= t('Découvrez des professionnels vérifiés et des opportunités dans le domaine') ?> « <?= e(t($name)) ?> » <?= t('en Afrique et à l\'international.') ?>
        </p>
    </header>

    <!-- Offres de la catégorie (affichées seulement si de vraies offres existent) -->
    <?php if (!empty($offers)): ?>
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <h2 class="h4 mb-0"><?= t('Offres dans ce domaine') ?></h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/search?tab=offres&category=' . urlencode($name))) ?>"><?= t('Voir toutes les opportunités') ?></a>
            </div>
            <div class="row g-3 g-lg-4">
                <?php foreach ($offers as $offer): ?>
                    <div class="col-md-6 col-lg-4"><?php View::partial('components/offer-card', ['offer' => $offer]); ?></div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Talents de la catégorie -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h2 class="h4 mb-0"><?= t('Talents en') ?> <?= e(t($name)) ?></h2>
            <?php if ($talentTotal > count($talentItems)): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/search?tab=profils&category=' . urlencode($name))) ?>"><?= t('Voir tous les talents') ?> (<?= $talentTotal ?>)</a>
            <?php endif; ?>
        </div>
        <?php if (!empty($talentItems)): ?>
            <div class="row g-3 g-lg-4">
                <?php foreach ($talentItems as $profile): ?>
                    <div class="col-md-6 col-lg-4"><?php View::partial('components/profile-card', ['profile' => $profile]); ?></div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <?php View::partial('components/empty-state', [
                'icon' => 'bi-person-plus',
                'title' => t('Bientôt des talents dans ce domaine'),
                'text' => t('Soyez parmi les premiers : créez votre profil et proposez vos compétences.'),
            ]); ?>
            <div class="text-center"><a class="btn btn-primary" href="<?= e(url('/register')) ?>"><?= t('Créer mon profil') ?></a></div>
        <?php endif; ?>
    </section>

    <!-- Maillage interne : autres domaines -->
    <section class="mb-4">
        <h2 class="h6 text-uppercase text-secondary mb-3" style="letter-spacing:.05em;"><?= t('Autres domaines') ?></h2>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($allCategories as $c): if ((string) $c['slug'] === $slug) continue; ?>
                <a class="badge badge-soft-primary text-decoration-none" href="<?= e(url('/categorie/' . rawurlencode((string) $c['slug']))) ?>"><?= e(t((string) $c['name'])) ?></a>
            <?php endforeach; ?>
        </div>
    </section>
</div>
