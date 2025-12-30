<?php
require_once 'config/config.php';
require_once 'config/db.php';

// Récupération des paramètres de recherche
$type = $_GET['type'] ?? '';
$categories = $_GET['categories'] ?? [];
$location = $_GET['location'] ?? '';
$query = $_GET['q'] ?? '';

// Si categories est une string (venant de l'accueil), la convertir en array
if (is_string($categories) && !empty($categories)) {
    $categories = [$categories];
}

// Validation du type
if (!in_array($type, ['prestataire', 'candidat'])) {
    $type = 'prestataire';
}

// Construction de la requête
$where = [];
$params = [];

if ($type === 'prestataire') {
    $sql = "SELECT u.*, p.*, c.nom as categorie_nom, c.icone as categorie_icone, c.couleur as categorie_couleur,
                   l.ville, l.region, l.pays
            FROM utilisateurs u
            JOIN profils_prestataires p ON u.id = p.utilisateur_id
            JOIN categories_services c ON p.categorie_id = c.id
            LEFT JOIN localisations l ON u.localisation_id = l.id
            WHERE u.statut = 'actif'";
} else {
    $sql = "SELECT DISTINCT u.*, cv.*, c.nom as categorie_nom, c.icone as categorie_icone, c.couleur as categorie_couleur,
                   l.ville, l.region, l.pays
            FROM utilisateurs u
            JOIN cvs cv ON u.id = cv.utilisateur_id
            JOIN categories_services c ON cv.categorie_id = c.id
            LEFT JOIN localisations l ON u.localisation_id = l.id
            WHERE u.statut = 'actif'";
}

// Filtres par catégories
if (!empty($categories) && is_array($categories) && !in_array('', $categories)) {
    $placeholders = str_repeat('?,', count($categories) - 1) . '?';
    if ($type === 'prestataire') {
        $sql .= " AND (p.categorie_id IN ($placeholders) OR EXISTS (SELECT 1 FROM prestataire_categories pc WHERE pc.prestataire_id = u.id AND pc.categorie_id IN ($placeholders)))";
        $params = array_merge($params, $categories, $categories);
    } else {
        $sql .= " AND (cv.categorie_id IN ($placeholders) OR EXISTS (SELECT 1 FROM cv_categories cc WHERE cc.cv_id = cv.id AND cc.categorie_id IN ($placeholders)))";
        $params = array_merge($params, $categories, $categories);
    }
} elseif (isset($_GET['categories']) && empty($categories)) {
    // Si le paramètre categories existe mais est vide, ne rien afficher
    $sql .= " AND 1=0";
}

// Filtre par localisation
if (!empty($location)) {
    $sql .= " AND (l.ville LIKE ? OR l.region LIKE ? OR l.pays LIKE ?)";
    $locationParam = '%' . $location . '%';
    $params = array_merge($params, [$locationParam, $locationParam, $locationParam]);
}

// Filtre par mot-clé
if (!empty($query)) {
    if ($type === 'prestataire') {
        $sql .= " AND (p.titre_professionnel LIKE ? OR p.description_services LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
    } else {
        $sql .= " AND (cv.titre_poste_recherche LIKE ? OR cv.competences LIKE ? OR u.nom LIKE ? OR u.prenom LIKE ?)";
    }
    $queryParam = '%' . $query . '%';
    $params = array_merge($params, [$queryParam, $queryParam, $queryParam, $queryParam]);
}

$sql .= " ORDER BY u.date_inscription DESC LIMIT 50";

try {
    global $database;
    $results = $database->fetchAll($sql, $params);
    
    // Récupérer toutes les catégories pour les filtres
    $allCategories = $database->fetchAll("SELECT * FROM categories_services WHERE actif = 1 ORDER BY nom");
    
} catch (Exception $e) {
    $results = [];
    $allCategories = [];
    $error = $e->getMessage();
}

$title = 'Recherche - LULU-OPEN';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="/lulu/assets/css/global-styles.css" rel="stylesheet">
    <link href="/lulu/assets/css/main.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Search Header -->
    <section class="search-hero-section">
        <div class="hero-background"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="search-hero-content text-center mb-5" data-aos="fade-up">
                        <h1 class="hero-title text-white mb-3">
                            <?= $type === 'prestataire' ? 'Trouvez le prestataire idéal' : 'Découvrez les meilleurs candidats' ?>
                        </h1>
                        <p class="hero-subtitle text-white-50 mb-4">
                            <?= count($results) ?> <?= $type === 'prestataire' ? 'prestataires' : 'candidats' ?> correspondent à votre recherche
                        </p>
                    </div>

                    <!-- Barre de recherche -->
                    <div class="search-container" data-aos="fade-up" data-aos-delay="200">
                        <form method="GET" class="search-form">
                            <div class="row g-3">
                                <div class="col-lg-2 col-md-3">
                                    <select class="form-select search-select" name="type">
                                        <option value="prestataire" <?= $type === 'prestataire' ? 'selected' : '' ?>>
                                            <i class="bi bi-briefcase"></i> Services
                                        </option>
                                        <option value="candidat" <?= $type === 'candidat' ? 'selected' : '' ?>>
                                            <i class="bi bi-person"></i> Emplois
                                        </option>
                                    </select>
                                </div>
                                <div class="col-lg-4 col-md-5">
                                    <select class="form-select search-select" name="categories[]" multiple size="1" id="categorySelect">
                                        <option value="" disabled>Sélectionnez des catégories...</option>
                                        <?php foreach ($allCategories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= in_array($cat['id'], $categories) ? 'selected' : '' ?>>
                                                <?= $cat['icone'] ?> <?= htmlspecialchars($cat['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <input type="text" class="form-control search-input" name="location" placeholder="🌍 Ville ou région" value="<?= htmlspecialchars($location) ?>">
                                </div>
                                <div class="col-lg-2 col-md-6">
                                    <input type="text" class="form-control search-input" name="q" placeholder="🔍 Mot-clé" value="<?= htmlspecialchars($query) ?>">
                                </div>
                                <div class="col-lg-1 col-md-6">
                                    <button type="submit" class="btn btn-search w-100">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Results Section -->
    <section class="results-section py-5">
        <div class="container">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger" data-aos="fade-up">Erreur: <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($results)): ?>
                <div class="text-center py-5" data-aos="fade-up">
                    <div class="empty-results">
                        <i class="bi bi-<?= $type === 'prestataire' ? 'person-x' : 'briefcase-fill' ?>" style="font-size: 4rem; color: #ccc;"></i>
                        <h3 class="mt-4 text-muted">
                            <?= $type === 'prestataire' ? 'Aucun prestataire trouvé' : 'Aucun candidat trouvé' ?>
                        </h3>
                        <p class="text-muted">Essayez de modifier vos critères de recherche ou explorez d'autres catégories</p>
                        <div class="mt-4">
                            <a href="/lulu/<?= $type === 'prestataire' ? 'services.php' : 'emplois.php' ?>" class="btn btn-<?= $type === 'prestataire' ? 'primary' : 'danger' ?> me-2">
                                <i class="bi bi-grid-3x3-gap"></i> Voir toutes les catégories
                            </a>
                            <a href="/lulu/" class="btn btn-outline-secondary">
                                <i class="bi bi-house"></i> Retour à l'accueil
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="results-grid">
                    <?php foreach ($results as $index => $result): ?>
                        <div class="result-card" data-aos="fade-up" data-aos-delay="<?= $index * 50 ?>">
                            <div class="card h-100 card-hover">
                                <div class="card-body">
                                    <div class="d-flex align-items-start mb-3">
                                        <div class="profile-avatar me-3">
                                            <?php if ($result['photo_profil']): ?>
                                                <img src="uploads/<?= $result['photo_profil'] ?>" alt="<?= htmlspecialchars($result['prenom']) ?>">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <?= strtoupper(substr($result['prenom'], 0, 1) . substr($result['nom'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-1">
                                                <?= htmlspecialchars($result['prenom'] . ' ' . $result['nom']) ?>
                                            </h5>
                                            <p class="card-subtitle text-muted mb-2">
                                                <?= $type === 'prestataire' ? htmlspecialchars($result['titre_professionnel']) : htmlspecialchars($result['titre_poste_recherche']) ?>
                                            </p>
                                            <div class="category-badge mb-2">
                                                <span class="badge" style="background-color: <?= $result['categorie_couleur'] ?>;">
                                                    <?= $result['categorie_icone'] ?> <?= htmlspecialchars($result['categorie_nom']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p class="card-text">
                                        <?= $type === 'prestataire' 
                                            ? htmlspecialchars(substr($result['description_services'], 0, 120)) . '...'
                                            : htmlspecialchars(substr($result['competences'], 0, 120)) . '...' ?>
                                    </p>
                                    
                                    <div class="card-footer-info">
                                        <?php if ($result['ville']): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($result['ville']) ?>
                                                <?= $result['region'] ? ', ' . htmlspecialchars($result['region']) : '' ?>
                                            </small>
                                        <?php endif; ?>
                                        
                                        <?php if ($type === 'prestataire' && $result['tarif_horaire']): ?>
                                            <?php
                                            $devise = $result['devise'] ?? 'EUR';
                                            $symbole = match($devise) {
                                                'EUR' => '€',
                                                'USD', 'CAD' => '$',
                                                'GBP' => '£',
                                                'CHF' => 'CHF',
                                                'MAD' => 'DH',
                                                'XOF' => 'CFA',
                                                'XAF' => 'FCFA',
                                                default => '€'
                                            };
                                            ?>
                                            <div class="price-tag">
                                                <strong><?= number_format($result['tarif_horaire'], 0) ?><?= $symbole ?>/h</strong>
                                            </div>
                                        <?php elseif ($type === 'candidat' && $result['salaire_souhaite']): ?>
                                            <?php
                                            $devise = $result['devise'] ?? 'EUR';
                                            $symbole = match($devise) {
                                                'EUR' => '€',
                                                'USD', 'CAD' => '$',
                                                'GBP' => '£',
                                                'CHF' => 'CHF',
                                                'MAD' => 'DH',
                                                'XOF' => 'CFA',
                                                'XAF' => 'FCFA',
                                                default => '€'
                                            };
                                            ?>
                                            <div class="price-tag">
                                                <strong><?= number_format($result['salaire_souhaite'], 0) ?><?= $symbole ?>/an</strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex gap-2">
                                        <a href="/lulu/profile-detail.php?id=<?= $result['utilisateur_id'] ?? $result['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
                                            <i class="bi bi-eye"></i> Voir le profil
                                        </a>
                                        <button class="btn btn-outline-primary btn-sm" onclick="contactUser(<?= $result['utilisateur_id'] ?? $result['id'] ?>)">
                                            <i class="bi bi-chat-dots"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Modal de connexion -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title"><i class="bi bi-lock text-primary"></i> Connexion requise</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-person-circle" style="font-size: 4rem; color: #0099FF;"></i>
                    <h5 class="mt-3 mb-2">Vous devez être connecté</h5>
                    <p class="text-muted">Pour contacter cet utilisateur, veuillez vous connecter ou créer un compte.</p>
                </div>
                <div class="modal-footer border-0 justify-content-center">
                    <a href="/lulu/login.php" class="btn btn-primary px-4">
                        <i class="bi bi-box-arrow-in-right"></i> Se connecter
                    </a>
                    <a href="/lulu/register.php" class="btn btn-outline-primary px-4">
                        <i class="bi bi-person-plus"></i> S'inscrire
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
        });
        
        async function contactUser(userId) {
            <?php if (isset($_SESSION['user_id'])): ?>
                try {
                    const response = await fetch('/lulu/api/send-message-init.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'recipient_id=' + userId
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Erreur lors de l\'initialisation de la conversation');
                    }
                } catch (error) {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue');
                }
            <?php else: ?>
                const modal = new bootstrap.Modal(document.getElementById('loginModal'));
                modal.show();
            <?php endif; ?>
        }
    </script>

    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
            --light-gray: #f8f9fa;
            --border-radius: 8px;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
            --font-family: 'Inter', sans-serif;
        }

        body {
            font-family: var(--font-family);
            padding-top: 76px;
        }

        .search-hero-section {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-blue) 100%);
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"/><circle cx="800" cy="300" r="150" fill="url(%23a)"/></svg>');
            opacity: 0.3;
        }

        .search-hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .search-container {
            position: relative;
            z-index: 1;
        }

        .search-form .form-control,
        .search-form .form-select {
            border-radius: var(--border-radius);
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            backdrop-filter: blur(10px);
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
        }

        .search-form .form-control::placeholder {
            color: #999;
        }

        .search-form .form-control:focus,
        .search-form .form-select:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.25rem rgba(0, 153, 255, 0.15);
            background: white;
        }

        #categorySelect {
            height: 48px;
        }

        #categorySelect option {
            padding: 0.5rem;
        }

        .btn-search {
            background: linear-gradient(135deg, var(--primary-blue), #00CCFF);
            border: none;
            color: white;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .btn-search:hover {
            background: linear-gradient(135deg, #0088DD, var(--primary-blue));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 153, 255, 0.3);
        }

        .search-select {
            font-weight: 500;
        }



        .results-section {
            background: var(--light-gray);
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .card-hover {
            transition: var(--transition);
            border: none;
            box-shadow: var(--shadow);
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .profile-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            overflow: hidden;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-blue), #00CCFF);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .card-footer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .price-tag {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .category-badge .badge {
            color: white;
            font-size: 0.8rem;
        }

        .empty-results {
            max-width: 400px;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .results-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .search-form .row {
                row-gap: 0.75rem;
            }



            .search-form .form-control,
            .search-form .form-select {
                font-size: 0.9rem;
            }
        }


    </style>
</body>
</html>