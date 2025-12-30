<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

require_once __DIR__ . '/../../models/Admin.php';
$adminModel = new Admin();
$stats = $adminModel->getDashboardStats();
$page_title = "Dashboard Admin - LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- CSS Global Admin -->
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
    
    <!-- AOS Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>
    
    <div class="admin-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="breadcrumb-custom">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item active">
                        <i class="bi bi-house-fill me-1"></i> Dashboard
                    </li>
                </ol>
            </div>
        </nav>
        
        <!-- En-tête -->
        <div class="row mb-4" data-aos="fade-down">
            <div class="col-12">
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-speedometer2 me-2"></i>Tableau de Bord Administration
                </h1>
                <p class="text-muted">Vue d'ensemble du système LULU-OPEN</p>
            </div>
        </div>
        
        <!-- KPIs Principaux -->
        <div class="row g-4 mb-4">
            <!-- Total Utilisateurs -->
            <div class="col-xl-3 col-md-6" data-aos="fade-up">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #0099FF, #00ccff);">
                            <i class="bi bi-people-fill text-white"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h6 class="text-muted mb-1">Total Utilisateurs</h6>
                            <h2 class="mb-0 fw-bold" style="color: var(--primary-dark);">
                                <?= number_format($stats['total_users']) ?>
                            </h2>
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> +<?= $stats['new_users_today'] ?> aujourd'hui
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Revenus Mensuels -->
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                            <i class="bi bi-cash-stack text-white"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h6 class="text-muted mb-1">Revenus ce mois</h6>
                            <h2 class="mb-0 fw-bold" style="color: var(--primary-dark);">
                                <?= number_format($stats['revenue_month'], 2) ?>€
                            </h2>
                            <small class="text-success">
                                <i class="bi bi-arrow-up"></i> +<?= $stats['revenue_growth'] ?>% vs mois dernier
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Abonnements Actifs -->
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                            <i class="bi bi-award-fill text-white"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h6 class="text-muted mb-1">Abonnements Actifs</h6>
                            <h2 class="mb-0 fw-bold" style="color: var(--primary-dark);">
                                <?= number_format($stats['active_subscriptions']) ?>
                            </h2>
                            <small class="text-warning">
                                <i class="bi bi-clock-history"></i> <?= $stats['expiring_soon'] ?> expirent bientôt
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Demandes en Attente -->
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                            <i class="bi bi-hourglass-split text-white"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <h6 class="text-muted mb-1">En Attente</h6>
                            <h2 class="mb-0 fw-bold" style="color: var(--primary-dark);">
                                <?= number_format($stats['pending_validations']) ?>
                            </h2>
                            <a href="<?= url('views/admin/validations.php') ?>" class="text-danger small">
                                <i class="bi bi-arrow-right"></i> Traiter maintenant
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Répartition par Rôle -->
        <div class="row g-4 mb-4">
            <div class="col-md-4" data-aos="fade-up">
                <div class="card-custom text-center p-4">
                    <i class="bi bi-briefcase-fill mb-3" style="font-size: 3rem; color: #0099FF;"></i>
                    <h3 class="fw-bold mb-1"><?= number_format($stats['user_distribution']['prestataires']) ?></h3>
                    <p class="text-muted mb-0">Prestataires</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card-custom text-center p-4">
                    <i class="bi bi-file-earmark-person-fill mb-3" style="font-size: 3rem; color: #00ccff;"></i>
                    <h3 class="fw-bold mb-1"><?= number_format($stats['user_distribution']['candidats']) ?></h3>
                    <p class="text-muted mb-0">Candidats</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card-custom text-center p-4">
                    <i class="bi bi-people-fill mb-3" style="font-size: 3rem; color: #9D4EDD;"></i>
                    <h3 class="fw-bold mb-1"><?= number_format($stats['user_distribution']['clients']) ?></h3>
                    <p class="text-muted mb-0">Clients</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                <div class="card-custom text-center p-4">
                    <i class="bi bi-person-badge mb-3" style="font-size: 3rem; color: #FFD700;"></i>
                    <h3 class="fw-bold mb-1"><?= number_format($stats['user_distribution']['prestataire_candidat']) ?></h3>
                    <p class="text-muted mb-0">Prestataire/Candidat</p>
                </div>
            </div>
        </div>

        
        <!-- Tableaux Activités -->
        <div class="row g-4">
            <!-- Dernières Inscriptions -->
            <div class="col-lg-6" data-aos="fade-up">
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5 class="mb-0">
                            <i class="bi bi-person-plus-fill me-2"></i>Dernières Inscriptions
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Rôle</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $recentUsers = $adminModel->getRecentUsers(5);
                                foreach ($recentUsers as $user): 
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $user['photo_profil'] ?? url('assets/images/default-avatar.png') ?>" 
                                                 class="rounded-circle me-2"
                                                 style="width: 35px; height: 35px; object-fit: cover;">
                                            <div>
                                                <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong>
                                                <br><small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-custom" style="background: <?= getRoleColor($user['type_utilisateur']) ?>;">
                                            <?= ucfirst($user['type_utilisateur']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($user['date_creation'])) ?></td>
                                    <td>
                                        <span class="badge badge-status-<?= $user['statut'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $user['statut'])) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-center bg-white">
                        <a href="<?= url('views/admin/users.php') ?>" class="btn btn-outline-custom btn-sm">
                            Voir tous les utilisateurs <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Derniers Paiements -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="100">
                <div class="card-custom">
                    <div class="card-header-custom">
                        <h5 class="mb-0">
                            <i class="bi bi-credit-card-fill me-2"></i>Derniers Paiements
                        </h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Utilisateur</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $recentPayments = $adminModel->getRecentPayments(5);
                                foreach ($recentPayments as $payment): 
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($payment['user_name']) ?></td>
                                    <td class="fw-bold text-success"><?= number_format($payment['montant'], 2) ?>€</td>
                                    <td><?= date('d/m/Y', strtotime($payment['date_paiement'])) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = match($payment['statut']) {
                                            'valide' => 'success',
                                            'en_attente' => 'warning',
                                            'echoue' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>">
                                            <?= ucfirst($payment['statut']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-center bg-white">
                        <a href="<?= url('views/admin/payments.php') ?>" class="btn btn-outline-custom btn-sm">
                            Voir tous les paiements <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .admin-content {
        margin-left: 260px;
        padding: 2rem;
        min-height: 100vh;
        background: #f8f9fa;
    }
    
    @media (max-width: 991.98px) {
        .admin-content {
            margin-left: 0;
            padding-top: calc(60px + 2rem);
        }
    }
    </style>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 600,
            once: true
        });
    </script>
</body>
</html>

<?php
function getRoleColor($role) {
    return match($role) {
        'prestataire' => '#0099FF',
        'candidat' => '#00ccff',
        'client' => '#9D4EDD',
        'admin' => '#dc3545',
        'prestataire_candidat' => '#FFD700',
        default => '#6c757d'
    };
}
?>
