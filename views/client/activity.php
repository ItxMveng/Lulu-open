<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_client();

$period = $_GET['period'] ?? '30';
$type = $_GET['type'] ?? '';

// Récupérer l'historique des consultations
$sql = "SELECT h.id, h.cible_type, h.cible_id, h.date_consultation,
               u.id as user_id, u.nom, u.prenom, u.photo_profil,
               pp.titre_professionnel,
               cv.titre_poste_recherche
        FROM historique_consultations h
        LEFT JOIN profils_prestataires pp ON h.cible_type = 'prestataire' AND h.cible_id = pp.utilisateur_id
        LEFT JOIN cvs cv ON h.cible_type = 'candidat' AND h.cible_id = cv.utilisateur_id
        LEFT JOIN utilisateurs u ON (h.cible_type = 'prestataire' AND pp.utilisateur_id = u.id) 
                                 OR (h.cible_type = 'candidat' AND cv.utilisateur_id = u.id)
        WHERE h.utilisateur_id = ?";

$params = [$_SESSION['user_id']];

if ($type) {
    $sql .= " AND h.cible_type = ?";
    $params[] = $type;
}

if ($period) {
    $sql .= " AND h.date_consultation >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = (int)$period;
}

$sql .= " ORDER BY h.date_consultation DESC LIMIT 50";

$consultations = $database->fetchAll($sql, $params);
$totalConsultations = count($consultations);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Activité - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; margin: 0; padding: 0; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: none; }
        .activity-card { transition: all 0.3s; }
        .activity-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .btn-primary-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); border: none; color: white; border-radius: 25px; }
        .avatar-initials { width: 80px; height: 80px; background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; font-size: 1.5rem; font-weight: 600; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= 'dashboard.php' ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Mon Activité</li>
            </ol>
        </nav>
    </div>

    <main class="container my-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 style="color: #000033;">
                    <i class="bi bi-clock-history me-2"></i>Mon Activité
                </h1>
                <p class="text-muted"><?= $totalConsultations ?> consultation(s) trouvée(s)</p>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-12">
                <div class="card-custom p-3">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Période</label>
                            <select name="period" class="form-select" onchange="this.form.submit()">
                                <option value="7" <?= $period == '7' ? 'selected' : '' ?>>7 derniers jours</option>
                                <option value="30" <?= $period == '30' ? 'selected' : '' ?>>30 derniers jours</option>
                                <option value="90" <?= $period == '90' ? 'selected' : '' ?>>90 derniers jours</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" onchange="this.form.submit()">
                                <option value="">Tous</option>
                                <option value="prestataire" <?= $type === 'prestataire' ? 'selected' : '' ?>>Prestataires</option>
                                <option value="candidat" <?= $type === 'candidat' ? 'selected' : '' ?>>Candidats</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <a href="activity.php" class="btn btn-outline-secondary" style="border-radius: 25px;">
                                <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php if (empty($consultations)): ?>
            <div class="text-center py-5">
                <i class="bi bi-clock-history" style="font-size: 5rem; color: #ccc;"></i>
                <h3 class="mt-3">Aucune consultation pour cette période</h3>
                <p class="text-muted">Commencez à explorer des profils !</p>
                <a href="recherche-prestataire.php" class="btn btn-primary-custom mt-3">
                    <i class="bi bi-search me-2"></i>Rechercher des prestataires
                </a>
                <a href="recherche-candidat.php" class="btn btn-primary-custom mt-3 ms-2">
                    <i class="bi bi-search me-2"></i>Rechercher des candidats
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($consultations as $c): 
                    $photoPath = $c['photo_profil'] ? '/lulu/uploads/profiles/' . basename($c['photo_profil']) : '';
                    $nom_complet = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
                    $titre = $c['cible_type'] === 'prestataire' ? ($c['titre_professionnel'] ?? '') : ($c['titre_poste_recherche'] ?? '');
                    $date_relative = '';
                    $diff = time() - strtotime($c['date_consultation']);
                    if ($diff < 3600) $date_relative = 'il y a ' . floor($diff/60) . ' min';
                    elseif ($diff < 86400) $date_relative = 'il y a ' . floor($diff/3600) . ' h';
                    else $date_relative = 'il y a ' . floor($diff/86400) . ' j';
                ?>
                <div class="col-12">
                    <div class="card-custom activity-card p-3">
                        <div class="row g-0 align-items-center">
                            <div class="col-md-2 text-center">
                                <?php if ($c['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)): ?>
                                    <img src="<?= $photoPath ?>" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;" alt="Avatar">
                                <?php else: 
                                    $initials = mb_substr($c['prenom'] ?? 'U', 0, 1) . mb_substr($c['nom'] ?? 'U', 0, 1);
                                ?>
                                    <div class="rounded-circle avatar-initials d-inline-flex align-items-center justify-content-center">
                                        <?= strtoupper($initials) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <span class="badge" style="background: <?= $c['cible_type'] === 'prestataire' ? '#0099FF' : '#00ccff' ?>;">
                                        <?= ucfirst($c['cible_type']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="col-md-7">
                                <h5 class="mb-2"><?= htmlspecialchars($nom_complet, ENT_QUOTES, 'UTF-8') ?></h5>
                                <?php if ($titre): ?>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-briefcase me-1"></i><?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                <?php endif; ?>
                                <div class="text-muted small">
                                    <i class="bi bi-clock"></i> Consulté <?= $date_relative ?>
                                </div>
                            </div>
                            
                            <div class="col-md-3 text-end">
                                <div class="d-flex flex-column gap-2">
                                    <a href="<?= $c['cible_type'] === 'prestataire' ? "profile-prestataire.php?id={$c['user_id']}" : "profile-candidat.php?id={$c['user_id']}" ?>" 
                                       class="btn btn-sm btn-primary-custom">
                                        <i class="bi bi-eye"></i> Revoir profil
                                    </a>
                                    <a href="conversation.php?id=<?= $c['user_id'] ?>" class="btn btn-sm btn-outline-primary" style="border-radius: 20px;">
                                        <i class="bi bi-chat-dots"></i> Contacter
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
