<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Admin.php';
if (!isset($_SESSION['user_id']) || $_SESSION['type_utilisateur'] !== 'admin') {
    header('Location: /lulu/login.php');
    exit;
}

$adminModel = new Admin();
$periode = $_GET['periode'] ?? '30j';
$summary = $adminModel->getStatisticsSummary($periode);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

    <div class="admin-content">
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="breadcrumb-custom">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Statistiques</li>
                </ol>
            </div>
        </nav>

        <h1 class="mb-4"><i class="bi bi-graph-up me-2"></i>Statistiques Avancées</h1>

        <!-- Filtres de Période -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="btn-group" role="group">
                    <a href="?periode=today" class="btn btn-outline-primary btn-sm <?= $periode === 'today' ? 'active' : '' ?>">Aujourd’hui</a>
                    <a href="?periode=7j" class="btn btn-outline-primary btn-sm <?= $periode === '7j' ? 'active' : '' ?>">7 jours</a>
                    <a href="?periode=30j" class="btn btn-outline-primary btn-sm <?= $periode === '30j' ? 'active' : '' ?>">30 jours</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card"><div class="card-body text-center">
                    <h3><?= $summary['users']['total'] ?></h3>
                    <p>Total Utilisateurs</p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body text-center">
                    <h3><?= $summary['subscriptions']['active'] ?></h3>
                    <p>Abonnements Actifs</p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body text-center">
                    <h3><?= number_format($summary['revenue']['amount'], 2) ?> €</h3>
                    <p>Revenus Période</p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body text-center">
                    <h3><?= $summary['validations']['pending'] ?></h3>
                    <p>Demandes en Attente</p>
                </div></div>
            </div>
        </div>

        <!-- Insights automatiques (IA) -->
        <div class="card-custom mt-4" data-aos="fade-up">
            <div class="card-header-custom">
                <h5 class="mb-0">
                    <i class="bi bi-stars me-2"></i>Insights automatiques (IA)
                </h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <?php
                    $insights = [];

                    if ($summary['revenue']['growth_rate'] > 10) {
                        $insights[] = "Les revenus ont augmenté de " . number_format($summary['revenue']['growth_rate'], 1) . "% sur la période. Pensez à analyser les campagnes ou offres qui ont le mieux marché pour les renforcer.";
                    } elseif ($summary['revenue']['growth_rate'] < -5) {
                        $insights[] = "Les revenus sont en baisse de " . number_format(abs($summary['revenue']['growth_rate']), 1) . "%. Vérifiez les échecs de paiements, les plans les moins performants et les éventuelles expirations d'abonnements.";
                    }

                    if ($summary['subscriptions']['churn_rate'] > 5) {
                        $insights[] = "Le taux de churn abonnements est élevé (" . number_format($summary['subscriptions']['churn_rate'], 1) . "%). Il serait utile d'analyser les raisons de résiliation et d'améliorer l'onboarding ou le support.";
                    }

                    if ($summary['validations']['pending'] > 10) {
                        $insights[] = "Il y a actuellement " . $summary['validations']['pending'] . " comptes en attente de validation. Traitez-les rapidement pour éviter de perdre des prestataires/candidats motivés.";
                    }

                    if ($summary['users']['growth_rate'] > 15) {
                        $insights[] = "La croissance du nombre d'utilisateurs est forte (" . number_format($summary['users']['growth_rate'], 1) . "%). Pensez à vérifier que l'infrastructure supporte bien la montée en charge.";
                    }

                    if ($summary['revenue']['avg_per_user'] < 5 && $summary['users']['total'] > 0) {
                        $insights[] = "Le revenu moyen par utilisateur est faible (" . number_format($summary['revenue']['avg_per_user'], 2) . "€). Vous pouvez envisager de créer des offres premium ou des add-ons.";
                    }

                    foreach ($insights as $insight) {
                        echo "<li><i class='bi bi-lightbulb me-2'></i> {$insight}</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
