<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats de recherche - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" id="mainNav">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-primary">LULU</span><span class="text-light">-OPEN</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="emplois.php">Emplois</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <?= $_SESSION['user_name'] ?? 'Utilisateur' ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="views/prestataire/dashboard.php">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-primary ms-2 px-3" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-primary ms-2 px-3" href="register.php">S'inscrire</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Search Header -->
    <section class="search-header" style="background: linear-gradient(135deg, #000033, #0099FF); padding: 120px 0 60px; color: white;">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="text-center mb-4">
                        <?= $type === 'prestataire' ? 'Prestataires' : 'Candidats' ?> trouvés
                    </h1>
                    
                    <!-- Search Form -->
                    <form method="GET" action="search.php" class="search-form-results">
                        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                        <div class="row g-2 justify-content-center">
                            <div class="col-md-4">
                                <input type="text" class="form-control" name="q" 
                                       value="<?= htmlspecialchars($query) ?>" 
                                       placeholder="Rechercher...">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="location" 
                                       value="<?= htmlspecialchars($location) ?>" 
                                       placeholder="Localisation">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-warning w-100">
                                    <i class="bi bi-search"></i> Rechercher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Results Section -->
    <section class="results-section py-5">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="results-info mb-4">
                        <h5><?= count($results) ?> résultat(s) trouvé(s)</h5>
                        <?php if ($query || $location): ?>
                            <p class="text-muted">
                                <?php if ($query): ?>
                                    Recherche: <strong><?= htmlspecialchars($query) ?></strong>
                                <?php endif; ?>
                                <?php if ($location): ?>
                                    <?= $query ? ' • ' : '' ?>Localisation: <strong><?= htmlspecialchars($location) ?></strong>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($results)): ?>
                        <div class="no-results text-center py-5">
                            <i class="bi bi-search display-1 text-muted mb-3"></i>
                            <h3>Aucun résultat trouvé</h3>
                            <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                            <a href="<?= $type === 'prestataire' ? 'services.php' : 'emplois.php' ?>" class="btn btn-primary">
                                Voir tous les <?= $type === 'prestataire' ? 'services' : 'candidats' ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($results as $result): ?>
                                <div class="col-lg-6">
                                    <div class="result-card">
                                        <div class="result-header">
                                            <div class="result-avatar">
                                                <?= strtoupper(substr($result['prenom'], 0, 1) . substr($result['nom'], 0, 1)) ?>
                                            </div>
                                            <div class="result-info">
                                                <h5 class="result-name">
                                                    <?= htmlspecialchars($result['prenom'] . ' ' . $result['nom']) ?>
                                                </h5>
                                                <p class="result-title">
                                                    <?= htmlspecialchars($result['titre_professionnel'] ?? $result['titre_poste_recherche'] ?? 'Professionnel') ?>
                                                </p>
                                                <div class="result-location">
                                                    <i class="bi bi-geo-alt"></i>
                                                    <?= htmlspecialchars($result['ville'] . ', ' . $result['pays']) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (isset($result['categorie_nom'])): ?>
                                            <div class="result-category">
                                                <span class="badge" style="background-color: <?= $result['categorie_couleur'] ?? '#0099FF' ?>">
                                                    <?= htmlspecialchars($result['categorie_nom']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="result-description">
                                            <?php if ($type === 'prestataire'): ?>
                                                <p><?= htmlspecialchars(substr($result['description_services'] ?? '', 0, 150)) ?>...</p>
                                                <?php if ($result['tarif_horaire']): ?>
                                                    <div class="result-price">
                                                        <strong><?= number_format($result['tarif_horaire'], 0) ?>€/h</strong>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <p><strong>Compétences:</strong> <?= htmlspecialchars(substr($result['competences'] ?? '', 0, 100)) ?>...</p>
                                                <p><strong>Niveau:</strong> <?= htmlspecialchars($result['niveau_experience'] ?? 'Non spécifié') ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <div class="result-actions">
                                            <a href="profile.php?id=<?= $result['id'] ?>&type=<?= $type ?>" class="btn btn-primary">
                                                Voir le profil
                                            </a>
                                            <?php if (isLoggedIn()): ?>
                                                <button class="btn btn-outline-primary" onclick="contactUser(<?= $result['id'] ?>)">
                                                    <i class="bi bi-chat"></i> Contacter
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
            --accent-red: #FF3366;
        }

        .search-form-results {
            max-width: 800px;
            margin: 0 auto;
        }

        .results-section {
            min-height: 60vh;
        }

        .result-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            height: 100%;
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }

        .result-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .result-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .result-info {
            flex: 1;
        }

        .result-name {
            margin: 0 0 0.25rem 0;
            color: var(--primary-dark);
        }

        .result-title {
            margin: 0 0 0.5rem 0;
            color: var(--primary-blue);
            font-weight: 500;
        }

        .result-location {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .result-category {
            margin-bottom: 1rem;
        }

        .result-description {
            margin-bottom: 1.5rem;
        }

        .result-price {
            color: var(--accent-red);
            font-size: 1.1rem;
            margin-top: 0.5rem;
        }

        .result-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .no-results {
            background: white;
            border-radius: 15px;
            margin: 2rem 0;
        }

        @media (max-width: 768px) {
            .result-actions {
                flex-direction: column;
            }
            
            .result-actions .btn {
                width: 100%;
            }
        }
    </style>

    <script>
        function contactUser(userId) {
            // Redirection vers la messagerie ou modal de contact
            window.location.href = `contact.php?user=${userId}`;
        }
    </script>
</body>
</html>