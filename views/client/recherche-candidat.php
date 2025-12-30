<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_client();

// Récupérer les filtres
$poste = $_GET['poste'] ?? '';
$ville = $_GET['ville'] ?? '';
$pays = $_GET['pays'] ?? '';
$exp_min = $_GET['exp_min'] ?? '';
$disponibilite = $_GET['disponibilite'] ?? '';

// Construire la requête SQL
$sql = "SELECT u.id, u.nom, u.prenom, u.photo_profil, l.ville, l.pays,
               cv.titre_poste_recherche, cv.experience_annees, cv.competences, cv.formations,
               cv.disponibilite_immediate
        FROM utilisateurs u
        JOIN cvs cv ON u.id = cv.utilisateur_id
        LEFT JOIN localisations l ON u.localisation_id = l.id
        WHERE u.statut = 'actif' AND u.type_utilisateur IN ('candidat', 'prestataire_candidat')";

$params = [];
if ($poste) {
    $sql .= " AND cv.titre_poste_recherche LIKE ?";
    $params[] = "%$poste%";
}
if ($ville) {
    $sql .= " AND l.ville LIKE ?";
    $params[] = "%$ville%";
}
if ($pays) {
    $sql .= " AND l.pays LIKE ?";
    $params[] = "%$pays%";
}
if ($exp_min !== '') {
    $sql .= " AND cv.experience_annees >= ?";
    $params[] = (int)$exp_min;
}
if ($disponibilite === 'immédiate') {
    $sql .= " AND cv.disponibilite_immediate = 1";
}

$sql .= " ORDER BY u.id DESC LIMIT 50";

$candidats = $database->fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher un Candidat - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); transition: all 0.3s ease; }
        .card-custom:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        .candidate-avatar { width: 80px; height: 80px; object-fit: cover; }
        .avatar-initials { width: 80px; height: 80px; background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; font-size: 1.5rem; font-weight: 600; }
        .skill-badge { background: #e7f3ff; color: #0d6efd; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; }
        .filter-card { position: sticky; top: 90px; border-radius: 15px; }
        .btn-primary-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); border: none; border-radius: 25px; padding: 0.5rem 1.5rem; font-weight: 500; }
        .btn-primary-custom:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,153,255,0.3); }
        .page-header { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; padding: 2rem 0; margin-bottom: 2rem; border-radius: 0 0 20px 20px; }
    </style>
</head>
<body>

<?php include __DIR__ . '/includes/navbar-client.php'; ?>

<div class="page-header">
    <div class="container">
        <h1 class="display-5 fw-bold mb-2">Rechercher un Candidat</h1>
        <p class="lead mb-0">Trouvez le talent parfait pour votre équipe</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Filtres -->
        <div class="col-lg-3">
            <div class="card card-custom filter-card">
                <div class="card-header" style="background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white;">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-funnel"></i> Filtres</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Poste recherché</label>
                            <input type="text" class="form-control" name="poste" value="<?= htmlspecialchars($poste) ?>" placeholder="Ex: Développeur">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pays</label>
                            <input type="text" class="form-control" name="pays" value="<?= htmlspecialchars($pays) ?>" placeholder="Ex: France">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ville</label>
                            <input type="text" class="form-control" name="ville" value="<?= htmlspecialchars($ville) ?>" placeholder="Ex: Paris">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Expérience minimum</label>
                            <select class="form-select" name="exp_min">
                                <option value="">Toute expérience</option>
                                <option value="0" <?= $exp_min === '0' ? 'selected' : '' ?>>Débutant (0-2 ans)</option>
                                <option value="3" <?= $exp_min === '3' ? 'selected' : '' ?>>Intermédiaire (3-5 ans)</option>
                                <option value="6" <?= $exp_min === '6' ? 'selected' : '' ?>>Confirmé (6+ ans)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Disponibilité</label>
                            <select class="form-select" name="disponibilite">
                                <option value="">Toutes</option>
                                <option value="immédiate" <?= $disponibilite === 'immédiate' ? 'selected' : '' ?>>Immédiate</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom w-100 mb-2">
                            <i class="bi bi-search"></i> Rechercher
                        </button>
                        <a href="recherche-candidat.php" class="btn btn-outline-secondary w-100" style="border-radius: 25px;">
                            <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Résultats -->
        <div class="col-lg-9">
            <div class="mb-3">
                <h5 class="fw-bold"><?= count($candidats) ?> candidats trouvés</h5>
            </div>
            
            <div class="row g-4">
                <?php foreach ($candidats as $c): ?>
                    <div class="col-md-6">
                        <div class="card card-custom h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <?php 
                                    $photo_path = '/lulu/uploads/profiles/' . basename($c['photo_profil'] ?? '');
                                    if ($c['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photo_path)): 
                                    ?>
                                        <img src="<?= $photo_path ?>" class="rounded-circle candidate-avatar me-3" alt="Avatar">
                                    <?php else: 
                                        $initials = mb_substr($c['prenom'], 0, 1) . mb_substr($c['nom'], 0, 1);
                                    ?>
                                        <div class="rounded-circle avatar-initials me-3 d-flex align-items-center justify-content-center">
                                            <?= strtoupper($initials) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 fw-bold"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom'], ENT_QUOTES, 'UTF-8') ?></h5>
                                        <p class="text-muted mb-1"><?= htmlspecialchars($c['titre_poste_recherche'] ?? 'Candidat', ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span class="badge" style="background: #0099FF;"><?= $c['experience_annees'] ?? 0 ?> ans d'exp.</span>
                                            <?php if ($c['ville'] || $c['pays']): ?>
                                                <small class="text-muted"><i class="bi bi-geo-alt"></i> 
                                                    <?= htmlspecialchars(trim(($c['ville'] ?? '') . ($c['ville'] && $c['pays'] ? ', ' : '') . ($c['pays'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <small style="color: #0099FF;"><i class="bi bi-calendar-check"></i> Disponible: <?= $c['disponibilite_immediate'] ? 'Immédiatement' : 'Sur demande' ?></small>
                                    </div>
                                </div>
                                
                                <?php if ($c['competences']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-block mb-2">Compétences:</small>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php 
                                            $skills = array_slice(explode(',', $c['competences']), 0, 4);
                                            foreach ($skills as $skill): 
                                            ?>
                                                <span class="skill-badge"><?= htmlspecialchars(trim($skill), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($c['formations']): ?>
                                    <p class="text-muted small mb-3">
                                        <i class="bi bi-mortarboard"></i> <?= htmlspecialchars(mb_substr($c['formations'], 0, 60), ENT_QUOTES, 'UTF-8') ?>...
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2">
                                    <a href="profile-candidat.php?id=<?= $c['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1" style="border-radius: 20px;">
                                        <i class="bi bi-eye"></i> Voir profil
                                    </a>
                                    <a href="conversation.php?id=<?= $c['id'] ?>" class="btn btn-primary btn-sm flex-grow-1" style="background: #0099FF; border: none; border-radius: 20px;">
                                        <i class="bi bi-chat-dots"></i> Contacter
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
