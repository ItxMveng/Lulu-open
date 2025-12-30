<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_client();

// Récupérer les catégories et localisations
global $database;
$categories = $database->fetchAll("SELECT * FROM categories_services ORDER BY nom");
$pays = $database->fetchAll("SELECT DISTINCT pays FROM localisations WHERE pays IS NOT NULL ORDER BY pays");
$villes = $database->fetchAll("SELECT DISTINCT ville FROM localisations WHERE ville IS NOT NULL ORDER BY ville");

// Filtres
$filters = [];
$params = [];
$where = "u.statut = 'actif' AND u.type_utilisateur IN ('prestataire', 'prestataire_candidat')";

if (!empty($_GET['categorie'])) {
    $where .= " AND p.categorie_id = ?";
    $params[] = $_GET['categorie'];
}
if (!empty($_GET['pays'])) {
    $where .= " AND l.pays = ?";
    $params[] = $_GET['pays'];
}
if (!empty($_GET['ville'])) {
    $where .= " AND l.ville LIKE ?";
    $params[] = '%' . $_GET['ville'] . '%';
}
if (!empty($_GET['tarif_max'])) {
    $where .= " AND p.tarif_horaire <= ?";
    $params[] = $_GET['tarif_max'];
}
if (!empty($_GET['note_min'])) {
    $where .= " AND p.note_moyenne >= ?";
    $params[] = $_GET['note_min'];
}

// Récupérer les prestataires avec devise
$sql = "SELECT u.id, u.nom, u.prenom, u.photo_profil, u.devise,
               l.ville, l.pays,
               p.titre_professionnel, p.description_services, p.note_moyenne, p.tarif_horaire,
               c.nom as categorie_nom, c.id as categorie_id
        FROM utilisateurs u
        JOIN profils_prestataires p ON u.id = p.utilisateur_id
        LEFT JOIN categories_services c ON p.categorie_id = c.id
        LEFT JOIN localisations l ON u.localisation_id = l.id
        WHERE $where
        ORDER BY p.note_moyenne DESC, u.id DESC
        LIMIT 100";

$prestataires = $database->fetchAll($sql, $params);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher un Prestataire - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
        }
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s; border: none; }
        .card-custom:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .provider-avatar { width: 80px; height: 80px; object-fit: cover; }
        .rating-stars { color: #ffc107; }
        .filter-card { position: sticky; top: 20px; }
        .btn-primary-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); border: none; color: white; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active">Rechercher Prestataire</li>
            </ol>
        </nav>
    </div>

<div class="container mb-5">
    <div class="row g-4">
        <!-- Filtres -->
        <div class="col-lg-3">
            <div class="card-custom filter-card">
                <div class="p-3" style="background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; border-radius: 15px 15px 0 0;">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-funnel me-2"></i>Filtres</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Catégorie</label>
                            <select class="form-select" name="categorie">
                                <option value="">Toutes</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($_GET['categorie'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nom'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pays</label>
                            <select class="form-select" name="pays">
                                <option value="">Tous</option>
                                <?php foreach ($pays as $p): ?>
                                    <option value="<?= $p['pays'] ?>" <?= ($_GET['pays'] ?? '') == $p['pays'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['pays'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Ville</label>
                            <input type="text" class="form-control" name="ville" value="<?= htmlspecialchars($_GET['ville'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: Paris">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tarif max/h</label>
                            <input type="number" class="form-control" name="tarif_max" value="<?= htmlspecialchars($_GET['tarif_max'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: 100">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Note minimum</label>
                            <select class="form-select" name="note_min">
                                <option value="">Toutes</option>
                                <option value="4.5" <?= ($_GET['note_min'] ?? '') == '4.5' ? 'selected' : '' ?>>4.5+ ⭐</option>
                                <option value="4.0" <?= ($_GET['note_min'] ?? '') == '4.0' ? 'selected' : '' ?>>4.0+ ⭐</option>
                                <option value="3.5" <?= ($_GET['note_min'] ?? '') == '3.5' ? 'selected' : '' ?>>3.5+ ⭐</option>
                                <option value="3.0" <?= ($_GET['note_min'] ?? '') == '3.0' ? 'selected' : '' ?>>3.0+ ⭐</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary-custom w-100 mb-2">
                            <i class="bi bi-search me-2"></i>Rechercher
                        </button>
                        <a href="recherche-prestataire.php" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle me-2"></i>Réinitialiser
                        </a>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Résultats -->
        <div class="col-lg-9">
            <div class="card-custom p-3 mb-4" data-aos="fade-down">
                <h4 class="mb-0" style="color: var(--primary-dark);">
                    <i class="bi bi-search me-2"></i><?= count($prestataires) ?> prestataires trouvés
                </h4>
            </div>
            
            <div class="row g-4" id="resultsContainer">
                <?php foreach ($prestataires as $p): 
                    $devise = $p['devise'] ?? 'EUR';
                    $symbole = $devise === 'USD' ? '$' : ($devise === 'GBP' ? '£' : '€');
                ?>
                    <div class="col-md-6 provider-item" data-aos="fade-up">
                        <div class="card-custom h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <?php 
                                    $photoPath = $p['photo_profil'] ? '/lulu/uploads/profiles/' . basename($p['photo_profil']) : '';
                                    if ($p['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)): 
                                    ?>
                                        <img src="<?= $photoPath ?>" 
                                             class="rounded-circle provider-avatar me-3" 
                                             alt="Avatar"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="rounded-circle provider-avatar me-3 bg-primary text-white align-items-center justify-content-center fs-3" style="display:none;">
                                            <?= strtoupper(mb_substr($p['prenom'], 0, 1) . mb_substr($p['nom'], 0, 1)) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded-circle provider-avatar me-3 bg-primary text-white d-flex align-items-center justify-content-center fs-3">
                                            <?= strtoupper(mb_substr($p['prenom'], 0, 1) . mb_substr($p['nom'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1 fw-bold"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom'], ENT_QUOTES, 'UTF-8') ?></h5>
                                        <p class="text-muted mb-1"><?= htmlspecialchars($p['titre_professionnel'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                            <span class="badge bg-light text-dark"><?= htmlspecialchars($p['categorie_nom'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if ($p['ville'] || $p['pays']): ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-geo-alt"></i> 
                                                    <?= htmlspecialchars(($p['ville'] ?? '') . ($p['ville'] && $p['pays'] ? ', ' : '') . ($p['pays'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="rating-stars mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star-fill <?= $i <= $p['note_moyenne'] ? '' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="text-muted ms-1"><?= number_format($p['note_moyenne'], 1) ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <p class="text-muted small mb-3"><?= htmlspecialchars(mb_substr($p['description_services'] ?? 'Aucune description', 0, 100), ENT_QUOTES, 'UTF-8') ?>...</p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary fs-5"><?= number_format($p['tarif_horaire'], 0, ',', ' ') ?> <?= $symbole ?>/h</span>
                                    <div class="btn-group">
                                        <a href="profile-prestataire.php?id=<?= $p['id'] ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="bi bi-eye"></i> Voir
                                        </a>
                                        <button class="btn btn-primary-custom btn-sm" onclick="contactProvider(<?= $p['id'] ?>)">
                                            <i class="bi bi-chat-dots"></i> Contacter
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function contactProvider(providerId) {
    window.location.href = `messages.php?contact=${providerId}`;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>AOS.init();</script>
</body>
</html>
