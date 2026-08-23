<?php
$stats = $stats ?? ['active_offers' => 0, 'applications' => 0, 'pending' => 0, 'unread_messages' => 0];
$recentApplications = $recentApplications ?? [];
$recentOffers = $recentOffers ?? [];
$user = $user ?? [];
$name = trim((string) ($user['name'] ?? ''));
?>
<section class="py-4">
    <?php $vstatus = current_verification_status(); ?>
    <?php if ($vstatus !== 'verified'): ?>
        <div class="lulu-alert <?= $vstatus === 'pending' ? 'lulu-alert-info' : ($vstatus === 'rejected' ? 'lulu-alert-danger' : 'lulu-alert-warning') ?> mb-4">
            <i class="bi <?= $vstatus === 'pending' ? 'bi-hourglass-split' : 'bi-shield-exclamation' ?>"></i>
            <div class="flex-grow-1">
                <?php if ($vstatus === 'pending'): ?>
                    <strong>Vérification en cours.</strong> Vous pourrez publier des offres une fois votre entreprise validée.
                <?php elseif ($vstatus === 'rejected'): ?>
                    <strong>Dossier non validé.</strong> Corrigez et soumettez à nouveau votre dossier de vérification.
                <?php else: ?>
                    <strong>Compte non vérifié.</strong> Faites vérifier votre entreprise pour publier des offres.
                <?php endif; ?>
                <a class="fw-semibold ms-1" href="<?= e(url('/entreprise/verification')) ?>">Gérer ma vérification →</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- En-tête -->
    <div class="dash-header p-4 p-lg-5 mb-4" style="background: linear-gradient(120deg, var(--lulu-primary-700), var(--lulu-accent-600) 120%);">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <p class="mb-1 opacity-75">Espace entreprise</p>
                <h1 class="h3 mb-0"><?= $name !== '' ? e($name) : 'Tableau de bord' ?></h1>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light fw-semibold" href="<?= e(url('/entreprise/offres/new')) ?>"><i class="bi bi-plus-lg me-1"></i> Publier une offre</a>
                <a class="btn btn-outline-light" href="<?= e(url('/entreprise/candidatures')) ?>">Candidatures</a>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <a class="stat-tile text-decoration-none" href="<?= e(url('/entreprise/offres')) ?>">
                <span class="stat-ic"><i class="bi bi-megaphone"></i></span>
                <span><span class="stat-n"><?= (int) $stats['active_offers'] ?></span><span class="stat-l d-block">Offres actives</span></span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile text-decoration-none" href="<?= e(url('/entreprise/candidatures')) ?>">
                <span class="stat-ic"><i class="bi bi-people"></i></span>
                <span><span class="stat-n"><?= (int) $stats['applications'] ?></span><span class="stat-l d-block">Candidatures reçues</span></span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile text-decoration-none" href="<?= e(url('/entreprise/candidatures')) ?>">
                <span class="stat-ic"><i class="bi bi-hourglass-split"></i></span>
                <span><span class="stat-n"><?= (int) $stats['pending'] ?></span><span class="stat-l d-block">À traiter</span></span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile text-decoration-none" href="<?= e(url('/messages')) ?>">
                <span class="stat-ic"><i class="bi bi-chat-dots"></i></span>
                <span><span class="stat-n"><?= (int) $stats['unread_messages'] ?></span><span class="stat-l d-block">Messages non lus</span></span>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Candidatures récentes -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Dernières candidatures</h2>
                        <a class="small fw-semibold" href="<?= e(url('/entreprise/candidatures')) ?>">Tout voir</a>
                    </div>
                    <?php if (empty($recentApplications)): ?>
                        <div class="text-center text-secondary py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                            <p class="mb-3">Aucune candidature pour le moment.</p>
                            <a class="btn btn-primary" href="<?= e(url('/entreprise/offres/new')) ?>">Publier une offre</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentApplications as $app): ?>
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold"><?= e((string) ($app['title'] ?? 'Offre')) ?></div>
                                        <div class="text-secondary small">Reçue le <?= e(date('d/m/Y', strtotime((string) ($app['created_at'] ?? 'now')))) ?></div>
                                    </div>
                                    <?php View::partial('components/status-badge', ['status' => (string) ($app['status'] ?? 'en_attente')]); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Mes offres + raccourcis -->
        <div class="col-lg-5">
            <div class="card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Mes offres</h2>
                        <a class="small fw-semibold" href="<?= e(url('/entreprise/offres')) ?>">Gérer</a>
                    </div>
                    <?php if (empty($recentOffers)): ?>
                        <p class="text-secondary small mb-0">Vous n'avez pas encore publié d'offre.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentOffers as $offer): ?>
                                <a class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center" href="<?= e(offer_url($offer)) ?>">
                                    <span class="text-truncate pe-2"><?= e((string) ($offer['title'] ?? 'Offre')) ?></span>
                                    <span class="badge <?= ($offer['status'] ?? '') === 'active' ? 'badge-soft-success' : 'text-bg-secondary' ?>"><?= e((string) ($offer['status'] ?? '')) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card">
                <div class="card-body p-4">
                    <h2 class="h6 text-secondary text-uppercase mb-3" style="letter-spacing: .05em;">Raccourcis</h2>
                    <div class="d-flex flex-column gap-2">
                        <a class="quick-link" href="<?= e(url('/entreprise/offres/new')) ?>"><span class="qic"><i class="bi bi-plus-lg"></i></span> Publier une offre</a>
                        <a class="quick-link" href="<?= e(url('/entreprise/profile/edit')) ?>"><span class="qic"><i class="bi bi-building-gear"></i></span> Profil entreprise</a>
                        <a class="quick-link" href="<?= e(url('/abonnement')) ?>"><span class="qic"><i class="bi bi-gem"></i></span> Mon abonnement</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
