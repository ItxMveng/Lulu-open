<?php $stats = $stats ?? ['users' => 0, 'subscriptions' => 0, 'offers' => 0, 'applications' => 0]; ?>
<div class="mb-4">
    <h1 class="h3 mb-1">Tableau de bord</h1>
    <p class="text-secondary mb-0">Vue d'ensemble de la plateforme.</p>
</div>
<div class="row g-3">
    <div class="col-6 col-lg-3">
        <a class="stat-tile text-decoration-none" href="<?= e(url('/admin/users')) ?>">
            <span class="stat-ic"><i class="bi bi-people"></i></span>
            <span><span class="stat-n"><?= (int) $stats['users'] ?></span><span class="stat-l d-block">Utilisateurs</span></span>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <a class="stat-tile text-decoration-none" href="<?= e(url('/admin/subscriptions')) ?>">
            <span class="stat-ic"><i class="bi bi-gem"></i></span>
            <span><span class="stat-n"><?= (int) $stats['subscriptions'] ?></span><span class="stat-l d-block">Abonnements actifs</span></span>
        </a>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <span class="stat-ic"><i class="bi bi-megaphone"></i></span>
            <span><span class="stat-n"><?= (int) $stats['offers'] ?></span><span class="stat-l d-block">Offres actives</span></span>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-tile">
            <span class="stat-ic"><i class="bi bi-send"></i></span>
            <span><span class="stat-n"><?= (int) $stats['applications'] ?></span><span class="stat-l d-block">Candidatures</span></span>
        </div>
    </div>
</div>

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <a class="quick-link" href="<?= e(url('/admin/users')) ?>"><span class="qic"><i class="bi bi-people"></i></span> Gérer les utilisateurs</a>
    </div>
    <div class="col-md-6">
        <a class="quick-link" href="<?= e(url('/admin/categories')) ?>"><span class="qic"><i class="bi bi-tags"></i></span> Gérer les catégories</a>
    </div>
    <div class="col-md-6">
        <a class="quick-link" href="<?= e(url('/admin/subscriptions')) ?>"><span class="qic"><i class="bi bi-gem"></i></span> Suivre les abonnements</a>
    </div>
    <div class="col-md-6">
        <a class="quick-link" href="<?= e(url('/api/admin-export?type=users')) ?>"><span class="qic"><i class="bi bi-download"></i></span> Exporter les utilisateurs (CSV)</a>
    </div>
</div>
