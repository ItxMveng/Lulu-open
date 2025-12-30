<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Recherche - LULU-OPEN' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/lulu/assets/css/main.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/lulu/">
                <span class="text-primary">LULU</span><span class="text-light">-OPEN</span>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/lulu/">Accueil</a>
                <a class="nav-link" href="/lulu/login">Connexion</a>
                <a class="nav-link btn btn-primary ms-2 px-3" href="/lulu/register">S'inscrire</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <!-- Filtres -->
            <div class="col-lg-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtres</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="/lulu/search">
                            <div class="mb-3">
                                <label class="form-label">Type</label>
                                <select name="type" class="form-select">
                                    <option value="">Tous</option>
                                    <option value="prestataire" <?= ($filters['type'] ?? '') === 'prestataire' ? 'selected' : '' ?>>Services</option>
                                    <option value="candidat" <?= ($filters['type'] ?? '') === 'candidat' ? 'selected' : '' ?>>Emplois</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Recherche</label>
                                <input type="text" name="q" class="form-control" 
                                       value="<?= htmlspecialchars($filters['query'] ?? '') ?>" 
                                       placeholder="Mots-clés...">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Localisation</label>
                                <input type="text" name="location" class="form-control" 
                                       value="<?= htmlspecialchars($filters['location'] ?? '') ?>" 
                                       placeholder="Ville...">
                            </div>
                            
                            <?php if (!empty($categories)): ?>
                            <div class="mb-3">
                                <label class="form-label">Catégorie</label>
                                <select name="category" class="form-select">
                                    <option value="">Toutes les catégories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>" 
                                                <?= ($filters['category'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Rechercher
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Résultats -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Résultats de recherche</h2>
                    <span class="text-muted"><?= $total_results ?? 0 ?> résultat(s)</span>
                </div>

                <?php if (empty($results)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-search display-1 text-muted"></i>
                        <h3 class="mt-3">Aucun résultat trouvé</h3>
                        <p class="text-muted">Essayez de modifier vos critères de recherche</p>
                        <a href="/lulu/" class="btn btn-primary">Retour à l'accueil</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($results as $result): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <?php if ($result['photo_profil']): ?>
                                                <img src="/lulu/uploads/<?= $result['photo_profil'] ?>" 
                                                     class="rounded-circle me-3" 
                                                     style="width: 50px; height: 50px; object-fit: cover;"
                                                     alt="Photo">
                                            <?php else: ?>
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-3" 
                                                     style="width: 50px; height: 50px;">
                                                    <?= strtoupper(substr($result['prenom'], 0, 1) . substr($result['nom'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($result['prenom'] . ' ' . $result['nom']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($result['ville'] ?? '') ?></small>
                                            </div>
                                        </div>
                                        
                                        <h6 class="card-title"><?= htmlspecialchars($result['titre_professionnel'] ?? $result['titre_poste_recherche'] ?? 'Professionnel') ?></h6>
                                        
                                        <?php if ($result['description_services'] ?? $result['competences']): ?>
                                            <p class="card-text small text-muted">
                                                <?= htmlspecialchars(substr($result['description_services'] ?? $result['competences'], 0, 100)) ?>...
                                            </p>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-light text-dark">
                                                <?= ucfirst($result['type_utilisateur']) ?>
                                            </span>
                                            <a href="/lulu/profile/<?= $result['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                Voir profil
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>">
                                            <?= $i ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>