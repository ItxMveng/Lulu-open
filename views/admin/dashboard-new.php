<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

$db = Database::getInstance()->getConnection();

// Statistiques de base
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
    'new_users_today' => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE DATE(date_inscription) = CURDATE()")->fetchColumn(),
    'revenue_month' => $db->query("SELECT COALESCE(SUM(amount/100), 0) FROM paiements_stripe WHERE status = 'succeeded' AND MONTH(created_at) = MONTH(CURDATE())")->fetchColumn(),
    'active_subscriptions' => $db->query("SELECT COUNT(*) FROM abonnements WHERE statut = 'Actif'")->fetchColumn(),
    'unread_messages' => $db->query("SELECT COUNT(*) FROM messages WHERE lu = 0")->fetchColumn(),
    'prestataires' => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'prestataire'")->fetchColumn(),
    'candidats' => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'candidat'")->fetchColumn(),
    'clients' => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'client'")->fetchColumn(),
    'prestataire_candidat' => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE type_utilisateur = 'prestataire_candidat'")->fetchColumn()
];

// Utilisateurs récents
$recentUsers = $db->query("SELECT prenom, nom, email, type_utilisateur, statut, date_inscription FROM utilisateurs ORDER BY date_inscription DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Paiements récents
$recentPayments = $db->query("
    SELECT ps.amount, ps.created_at, ps.status, u.prenom, u.nom 
    FROM paiements_stripe ps 
    JOIN utilisateurs u ON ps.user_id = u.id 
    ORDER BY ps.created_at DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>
    
    <div class="admin-content">
        <div class="container-fluid">
            <h1 class="mb-4"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</h1>
            
            <!-- KPIs -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-people-fill fs-1 me-3"></i>
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['total_users']) ?></h3>
                                    <p class="mb-0">Total Utilisateurs</p>
                                    <small>+<?= $stats['new_users_today'] ?> aujourd'hui</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-cash-stack fs-1 me-3"></i>
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['revenue_month'], 2) ?>€</h3>
                                    <p class="mb-0">Revenus ce mois</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-award-fill fs-1 me-3"></i>
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['active_subscriptions']) ?></h3>
                                    <p class="mb-0">Abonnements Actifs</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-envelope-fill fs-1 me-3"></i>
                                <div>
                                    <h3 class="mb-0"><?= number_format($stats['unread_messages']) ?></h3>
                                    <p class="mb-0">Messages non lus</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Répartition utilisateurs -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-briefcase-fill text-primary" style="font-size: 3rem;"></i>
                            <h3 class="mt-2"><?= number_format($stats['prestataires']) ?></h3>
                            <p class="text-muted">Prestataires</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-file-earmark-person-fill text-info" style="font-size: 3rem;"></i>
                            <h3 class="mt-2"><?= number_format($stats['candidats']) ?></h3>
                            <p class="text-muted">Candidats</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-people-fill text-purple" style="font-size: 3rem;"></i>
                            <h3 class="mt-2"><?= number_format($stats['clients']) ?></h3>
                            <p class="text-muted">Clients</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="bi bi-person-badge text-warning" style="font-size: 3rem;"></i>
                            <h3 class="mt-2"><?= number_format($stats['prestataire_candidat']) ?></h3>
                            <p class="text-muted">Prestataire/Candidat</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tableaux -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-person-plus-fill me-2"></i>Dernières Inscriptions</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Type</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary"><?= ucfirst($user['type_utilisateur']) ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($user['date_inscription'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="<?= url('views/admin/users.php') ?>" class="btn btn-outline-primary btn-sm">
                                Voir tous les utilisateurs
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-credit-card-fill me-2"></i>Derniers Paiements</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Utilisateur</th>
                                            <th>Montant</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentPayments as $payment): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($payment['prenom'] . ' ' . $payment['nom']) ?></td>
                                            <td class="text-success fw-bold"><?= number_format($payment['amount']/100, 2) ?>€</td>
                                            <td><?= date('d/m/Y H:i', strtotime($payment['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-center">
                            <a href="<?= url('views/admin/payments.php') ?>" class="btn btn-outline-success btn-sm">
                                Voir tous les paiements
                            </a>
                        </div>
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
        }
    }
    
    .text-purple {
        color: #9D4EDD !important;
    }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>