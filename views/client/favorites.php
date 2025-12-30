<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../models/Favorite.php';

require_client();

$favoriteModel = new Favorite();
$filters = ['type' => $_GET['type'] ?? ''];
$page = $_GET['page'] ?? 1;
$favorites = $favoriteModel->getAll($_SESSION['user_id'], $filters, $page, 20);
$totalFavorites = $favoriteModel->count($_SESSION['user_id'], $filters);
$page_title = "Mes Favoris - LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
        }
        body { background: #f8f9fa; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s; }
        .favorite-card { position: relative; }
        .favorite-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .btn-primary-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); border: none; color: white; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= 'dashboard.php' ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Mes Favoris</li>
            </ol>
        </nav>
    </div>

    <main class="container my-4">
        <div class="row mb-4" data-aos="fade-down">
            <div class="col-12">
                <h1 style="color: var(--primary-dark);">
                    <i class="bi bi-heart-fill text-danger me-2"></i>Mes Favoris
                </h1>
                <p class="text-muted"><?= $totalFavorites ?> profil(s) en favoris</p>
            </div>
        </div>
        
        <!-- Filtres -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card-custom p-3">
                    <form method="GET" id="filterForm">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select" onchange="this.form.submit()">
                                    <option value="">Tous</option>
                                    <option value="prestataire" <?= $filters['type'] === 'prestataire' ? 'selected' : '' ?>>Prestataires</option>
                                    <option value="candidat" <?= $filters['type'] === 'candidat' ? 'selected' : '' ?>>Candidats</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-secondary" onclick="window.location.href='<?= 'favorites.php' ?>'">
                                    <i class="bi bi-x-circle"></i> Réinitialiser
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Liste favoris -->
        <?php if (empty($favorites)): ?>
            <div class="text-center py-5" data-aos="fade-up">
                <i class="bi bi-heart" style="font-size: 5rem; color: #ccc;"></i>
                <h3 class="mt-3">Aucun favori pour le moment</h3>
                <p class="text-muted">Commencez à ajouter des profils à vos favoris !</p>
                <a href="<?= url('services.php') ?>" class="btn btn-primary-custom mt-3">
                    <i class="bi bi-search me-2"></i>Rechercher des profils
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($favorites as $fav): ?>
                <div class="col-lg-4 col-md-6" data-aos="fade-up">
                    <div class="card-custom favorite-card" data-favorite-id="<?= $fav['id'] ?>">
                        <div class="position-relative">
                            <?php 
                            $photoPath = $fav['photo'] ? '/lulu/uploads/profiles/' . basename($fav['photo']) : '';
                            if ($fav['photo'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)): 
                            ?>
                                <img src="<?= $photoPath ?>" 
                                     class="w-100" 
                                     style="height: 200px; object-fit: cover; border-radius: 15px 15px 0 0;"
                                     alt="<?= htmlspecialchars($fav['nom'], ENT_QUOTES, 'UTF-8') ?>"
                                     onerror="this.src='/lulu/assets/images/default-avatar.png'">
                            <?php else: ?>
                                <div class="w-100 bg-primary text-white d-flex align-items-center justify-content-center" 
                                     style="height: 200px; border-radius: 15px 15px 0 0; font-size: 4rem;">
                                    <?= strtoupper(mb_substr($fav['nom'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <span class="badge position-absolute top-0 end-0 m-2" 
                                  style="background: <?= $fav['type_cible'] === 'prestataire' ? '#0099FF' : '#00ccff' ?>;">
                                <?= ucfirst($fav['type_cible']) ?>
                            </span>
                            <button class="btn btn-danger btn-sm position-absolute bottom-0 end-0 m-2 remove-favorite"
                                    data-id="<?= $fav['cible_id'] ?>"
                                    data-type="<?= $fav['type_cible'] ?>">
                                <i class="bi bi-heart-fill"></i>
                            </button>
                        </div>
                        <div class="p-3">
                            <h5 class="mb-2"><?= htmlspecialchars($fav['nom'], ENT_QUOTES, 'UTF-8') ?></h5>
                            <?php if (!empty($fav['titre'])): ?>
                                <p class="text-muted small mb-2"><?= htmlspecialchars($fav['titre'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-calendar"></i>
                                Ajouté le <?= date('d/m/Y', strtotime($fav['date_ajout'])) ?>
                            </p>
                            <div class="d-flex gap-2">
                                <a href="<?= $fav['type_cible'] === 'prestataire' ? "profile-prestataire.php?id={$fav['cible_id']}" : "profile-candidat.php?id={$fav['cible_id']}" ?>" 
                                   class="btn btn-sm btn-primary-custom flex-grow-1">
                                    <i class="bi bi-eye"></i> Voir profil
                                </a>
                                <a href="conversation.php?id=<?= $fav['cible_id'] ?>" 
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-envelope"></i>
                                </a>
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
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="<?= asset('js/favorites.js') ?>"></script>
    <script>AOS.init();</script>
</body>
</html>
