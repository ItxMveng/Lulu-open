<?php
require_once 'config/config.php';

// Récupération des catégories d'emplois
try {
    global $database;
    $categories = $database->fetchAll("SELECT * FROM categories_services WHERE actif = 1 ORDER BY nom");
    $totalCandidats = $database->fetch("SELECT COUNT(*) as count FROM cvs c JOIN utilisateurs u ON c.utilisateur_id = u.id WHERE u.statut = 'actif'")['count'];
} catch (Exception $e) {
    $categories = [];
    $totalCandidats = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous nos Domaines d'Emploi - LULU-OPEN</title>
    <meta name="description" content="Découvrez tous nos domaines d'emploi. Trouvez les candidats qualifiés et expérimentés pour vos équipes.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="assets/css/global-styles.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section" style="background: linear-gradient(135deg, #0099FF, #28A745); padding: 120px 0 80px; color: white;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-up">
                    <h1 class="hero-title mb-4">
                        Trouvez les <span class="text-warning">meilleurs talents</span>
                        pour vos équipes
                    </h1>
                    <p class="hero-subtitle mb-5">
                        Plus de <?= $totalCandidats ?> candidats qualifiés et motivés.
                        Développeurs, designers, managers, techniciens... Tous les profils professionnels dont vous avez besoin.
                    </p>

                    <!-- Search Bar -->
                    <form method="GET" action="search.php" class="search-form-hero">
                        <input type="hidden" name="type" value="candidat">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="q" placeholder="Quel profil recherchez-vous ?">
                            </div>
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="location" placeholder="Ville">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-lg-6" data-aos="fade-left">
                    <div class="hero-stats text-center">
                        <div class="row">
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number text-warning"><?= $totalCandidats ?>+</h3>
                                    <p class="stat-label">Candidats</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number text-warning"><?= count($categories) ?>+</h3>
                                    <p class="stat-label">Domaines</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number text-warning">85%</h3>
                                    <p class="stat-label">Embauche rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5" data-aos="fade-up">
                    <h2 class="section-title">Tous nos domaines d'emploi</h2>
                    <p class="section-subtitle">Des talents qualifiés dans tous les secteurs</p>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($categories as $index => $category): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6" data-aos="fade-up" data-aos-delay="<?= ($index % 12) * 50 ?>">
                        <a href="search.php?type=candidat&categories[]=<?= $category['id'] ?>" class="category-card-link text-decoration-none">
                            <div class="category-card">
                                <div class="category-icon" style="background: <?= $category['couleur'] ?? '#0099FF' ?>">
                                    <span style="font-size: 2rem;"><?= $category['icone'] ?? '💼' ?></span>
                                </div>
                                <h5><?= htmlspecialchars($category['nom']) ?></h5>
                                <p class="text-muted small"><?= htmlspecialchars($category['description'] ?? 'Domaines professionnels') ?></p>
                                <div class="category-arrow">
                                    <i class="bi bi-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="row mt-5">
                <div class="col-12 text-center" data-aos="fade-up">
                    <p class="lead mb-4">Vous ne trouvez pas ce que vous cherchez ?</p>
                    <a href="search.php?type=candidat" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-person-search me-2"></i>Rechercher un candidat
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5" style="background: linear-gradient(135deg, #0099FF, #28A745); color: white;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center" data-aos="fade-up">
                    <h2 class="cta-title mb-4">Vous cherchez du travail ?</h2>
                    <p class="cta-subtitle mb-5">Créez votre CV et rejoignez notre plateforme pour trouver votre prochain poste idéal</p>

                    <div class="cta-buttons">
                        <a href="register.php?type=professionnel" class="btn btn-warning btn-lg me-3 mb-3">
                            <i class="bi bi-person-add me-2"></i>Publier mon CV
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg mb-3">
                            Se connecter
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
            --accent-red: #FF3366;
            --success-green: #28A745;
            --warning-orange: #FFC107;
            --light-gray: #f8f9fa;
            --medium-gray: #6c757d;
            --border-radius: 8px;
            --border-radius-lg: 15px;
            --shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 15px 35px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
            --font-family: 'Inter', sans-serif;
        }

        body {
            font-family: var(--font-family);
        }

        #mainNav {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue)) !important;
        }

        .search-form-hero {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
        }

        .search-form-hero .form-control {
            border: none;
            border-radius: var(--border-radius);
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.9);
        }

        .category-card-link {
            text-decoration: none;
            color: inherit;
        }

        .category-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-dark), var(--accent-warning));
            transform: scaleX(0);
            transition: var(--transition);
        }

        .category-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
        }

        .category-card:hover::before {
            transform: scaleX(1);
        }

        .category-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
        }

        .category-card h5 {
            color: var(--primary-dark);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .category-arrow {
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            opacity: 0;
            transition: var(--transition);
            color: var(--primary-dark);
        }

        .category-card:hover .category-arrow {
            opacity: 1;
            transform: translateX(5px);
        }

        .hero-stats .stat-item {
            padding: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .section-subtitle {
            font-size: 1.1rem;
            color: #6c757d;
        }
    </style>

    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    </script>
</body>
</html>
