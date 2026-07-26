<?php
$stats = $stats ?? ['applications' => 0, 'saved_searches' => 0, 'unread_messages' => 0, 'notifications' => 0];
$recentApplications = $recentApplications ?? [];
$user = $user ?? [];
$name = trim((string) ($user['name'] ?? ''));
?>
<section class="py-4">
    <!-- En-tête -->
    <div class="dash-header p-4 p-lg-5 mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <p class="mb-1 opacity-75">Espace candidat</p>
                <h1 class="h3 mb-0">Bonjour<?= $name !== '' ? ', ' . e($name) : '' ?></h1>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-light fw-semibold" href="<?= e(url('/search')) ?>"><i class="bi bi-search me-1"></i> Trouver une offre</a>
                <a class="btn btn-outline-light" href="<?= e(url('/client/profile/edit')) ?>">Mon profil</a>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <a class="stat-tile text-decoration-none" href="<?= e(url('/client/candidatures')) ?>">
                <span class="stat-ic"><i class="bi bi-send"></i></span>
                <span><span class="stat-n"><?= (int) $stats['applications'] ?></span><span class="stat-l d-block">Candidatures</span></span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile text-decoration-none" href="<?= e(url('/messages')) ?>">
                <span class="stat-ic"><i class="bi bi-chat-dots"></i></span>
                <span><span class="stat-n"><?= (int) $stats['unread_messages'] ?></span><span class="stat-l d-block">Messages non lus</span></span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <a class="stat-tile text-decoration-none" href="<?= e(url('/favoris')) ?>">
                <span class="stat-ic"><i class="bi bi-bookmark-star"></i></span>
                <span><span class="stat-n"><?= (int) $stats['saved_searches'] ?></span><span class="stat-l d-block">Recherches sauvées</span></span>
            </a>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile">
                <span class="stat-ic"><i class="bi bi-bell"></i></span>
                <span><span class="stat-n"><?= (int) $stats['notifications'] ?></span><span class="stat-l d-block">Notifications</span></span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Candidatures récentes -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h5 mb-0">Mes candidatures récentes</h2>
                        <a class="small fw-semibold" href="<?= e(url('/client/candidatures')) ?>">Tout voir</a>
                    </div>
                    <?php if (empty($recentApplications)): ?>
                        <div class="text-center text-secondary py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                            <p class="mb-3">Vous n'avez pas encore postulé.</p>
                            <a class="btn btn-primary" href="<?= e(url('/search')) ?>">Parcourir les offres</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentApplications as $app): ?>
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold"><?= e((string) ($app['title'] ?? 'Offre')) ?></div>
                                        <div class="text-secondary small"><?= e(date('d/m/Y', strtotime((string) ($app['created_at'] ?? 'now')))) ?></div>
                                    </div>
                                    <?php View::partial('components/status-badge', ['status' => (string) ($app['status'] ?? 'en_attente')]); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Raccourcis -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 class="h6 text-secondary text-uppercase mb-3" style="letter-spacing: .05em;">Raccourcis</h2>
                    <div class="d-flex flex-column gap-2">
                        <a class="quick-link" href="<?= e(url('/search')) ?>"><span class="qic"><i class="bi bi-search"></i></span> Rechercher une offre</a>
                        <a class="quick-link" href="<?= e(url('/client/candidatures')) ?>"><span class="qic"><i class="bi bi-send"></i></span> Mes candidatures</a>
                        <a class="quick-link" href="<?= e(url('/messages')) ?>"><span class="qic"><i class="bi bi-chat-dots"></i></span> Messagerie</a>
                        <a class="quick-link" href="<?= e(url('/client/profile/edit')) ?>"><span class="qic"><i class="bi bi-person-gear"></i></span> Modifier mon profil</a>
                        <a class="quick-link" href="<?= e(url('/abonnement')) ?>"><span class="qic"><i class="bi bi-gem"></i></span> Mon abonnement</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
