<?php
require_once __DIR__ . '/../../includes/middleware.php';
require_client();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/SavedSearch.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['type_utilisateur'] !== 'client') {
    redirect('login.php');
}

$searchModel = new SavedSearch();

if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['id'])) {
    $searchModel->delete($_POST['id'], $_SESSION['user_id']);
    redirect('views/client/saved-searches.php');
}

$savedSearches = $searchModel->getAll($_SESSION['user_id']);
$page_title = "Recherches Sauvegardées - LULU-OPEN";

function buildSearchUrl($search) {
    $criteres = json_decode($search['criteres'], true);
    $baseUrl = $search['type'] === 'prestataire' ? '/lulu/services.php' : '/lulu/emplois.php';
    $params = $criteres ? http_build_query($criteres) : '';
    return $baseUrl . ($params ? '?' . $params : '');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --primary-dark: #000033; --primary-blue: #0099FF; }
        body { background: #f8f9fa; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .saved-search-card { transition: all 0.3s; border-left: 4px solid var(--primary-blue); }
        .saved-search-card:hover { transform: translateX(10px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= 'dashboard.php' ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Recherches Sauvegardées</li>
            </ol>
        </nav>
    </div>

    <main class="container my-4">
        <div class="row mb-4">
            <div class="col-12">
                <h1 style="color: var(--primary-dark);">
                    <i class="bi bi-bookmark-fill me-2"></i>Recherches Sauvegardées
                </h1>
                <p class="text-muted"><?= count($savedSearches) ?> recherche(s) sauvegardée(s)</p>
            </div>
        </div>
        
        <?php if (empty($savedSearches)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bookmark" style="font-size: 5rem; color: #ccc;"></i>
                <h3 class="mt-3">Aucune recherche sauvegardée</h3>
                <p class="text-muted">Effectuez des recherches et cliquez sur "Sauvegarder"</p>
                <a href="<?= url('services.php') ?>" class="btn btn-primary mt-3">
                    <i class="bi bi-search me-2"></i>Effectuer une recherche
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($savedSearches as $search): ?>
                <div class="col-lg-6">
                    <div class="card-custom saved-search-card">
                        <div class="d-flex justify-content-between align-items-start p-4">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-search me-3" style="font-size: 2rem; color: var(--primary-blue);"></i>
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($search['nom'], ENT_QUOTES, 'UTF-8') ?></h5>
                                        <span class="badge" style="background: <?= $search['type'] === 'prestataire' ? '#0099FF' : '#00ccff' ?>;">
                                            <?= ucfirst($search['type']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="text-muted small mb-2">Critères :</h6>
                                    <?php
                                    $criteres = json_decode($search['criteres'], true);
                                    if ($criteres):
                                    ?>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($criteres as $key => $value): ?>
                                                <?php if (!empty($value)): ?>
                                                    <span class="badge bg-light text-dark">
                                                        <?= ucfirst($key) ?>: <?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex align-items-center gap-2 text-muted small mb-3">
                                    <i class="bi bi-bell-fill"></i>
                                    <span>Alerte : 
                                        <?php if ($search['alerte_active']): ?>
                                            <strong class="text-success">Active</strong> (<?= ucfirst($search['frequence_alerte']) ?>)
                                        <?php else: ?>
                                            <span class="text-muted">Désactivée</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="text-muted small">
                                    <i class="bi bi-calendar"></i> Créée le <?= date('d/m/Y', strtotime($search['date_creation'])) ?>
                                </div>
                                
                                <div class="mt-3 d-flex gap-2">
                                    <a href="<?= buildSearchUrl($search) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-play-circle"></i> Relancer
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette recherche ?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $search['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
