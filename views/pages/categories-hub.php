<?php $categories = $categories ?? []; ?>
<div class="mb-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(url('/')) ?>"><?= t('Accueil') ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= t('Domaines') ?></li>
        </ol>
    </nav>
    <h1 class="fw-bold mb-2"><?= t('Explorez par domaine') ?></h1>
    <p class="lead text-secondary mb-0" style="max-width: 60ch;"><?= t('Talents vérifiés et opportunités dans tous les secteurs, en Afrique et à l\'international.') ?></p>
</div>

<div class="row g-3 g-lg-4">
    <?php foreach ($categories as $category): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <a class="category-card h-100" href="<?= e(url('/categorie/' . rawurlencode((string) ($category['slug'] ?? '')))) ?>">
                <span class="category-icon"><i class="bi <?= e((string) ($category['icon'] ?? 'bi-grid')) ?>"></i></span>
                <span class="fw-semibold"><?= e(t((string) ($category['name'] ?? ''))) ?></span>
            </a>
        </div>
    <?php endforeach; ?>
</div>
