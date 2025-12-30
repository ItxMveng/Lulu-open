<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LULU-OPEN - Marketplace des Talents</title>
    <meta name="description" content="Trouvez les meilleurs prestataires de services et talents près de chez vous">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- AOS CSS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/global-styles.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/animations.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section" id="hero">
        <div class="hero-background"></div>
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6" data-aos="fade-up">
                    <h1 class="hero-title mb-4">
                        Trouvez le <span class="text-primary">talent parfait</span> 
                        pour vos projets
                    </h1>
                    <p class="hero-subtitle mb-5">
                        Connectez-vous avec des milliers de prestataires qualifiés et de candidats talentueux. 
                        La marketplace qui révolutionne la recherche de services et d'emplois.
                    </p>
                    
                    <!-- Search Bar -->
                    <div class="search-container mb-4">
                        <form class="search-form" method="GET" action="/lulu/search">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <select class="form-select search-select" name="type" required>
                                        <option value="">Type de recherche</option>
                                        <option value="prestataire">Services</option>
                                        <option value="candidat">Emplois</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button" id="categoryDropdown" data-bs-toggle="dropdown">
                                            <span id="categoryText">Sélectionner catégories</span>
                                        </button>
                                        <div class="dropdown-menu w-100 p-2" style="max-height: 300px; overflow-y: auto;">
                                            <?php if (!empty($categories)): ?>
                                                <?php foreach ($categories as $category): ?>
                                                    <div class="form-check">
                                                        <input class="form-check-input category-checkbox" type="checkbox" name="categories[]" value="<?= $category['id'] ?>" id="cat_<?= $category['id'] ?>">
                                                        <label class="form-check-label" for="cat_<?= $category['id'] ?>">
                                                            <?= $category['icone'] ?> <?= htmlspecialchars($category['nom']) ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control search-input" placeholder="Ville" name="location">
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary search-btn w-100">
                                        <i class="bi bi-search"></i> Rechercher
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number text-primary"><?= number_format($stats['prestataires'] ?? 2500) ?>+</h3>
                                    <p class="stat-label">Prestataires</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number text-primary"><?= number_format($stats['cvs'] ?? 1200) ?>+</h3>
                                    <p class="stat-label">CV Actifs</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item">
                                    <h3 class="stat-number text-primary"><?= $stats['satisfaction'] ?? 98 ?>%</h3>
                                    <p class="stat-label">Satisfaction</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="hero-image">
                            <?php if (!empty($recent_profiles)): ?>
                            <?php foreach (array_slice($recent_profiles, 0, 3) as $index => $profile): ?>
                                <div class="floating-card card-<?= $index + 1 ?>">
                                    <div class="card-content">
                                        <div class="avatar">
                                            <?php if ($profile['photo_profil']): ?>
                                                <img src="uploads/<?= $profile['photo_profil'] ?>" alt="<?= htmlspecialchars($profile['prenom']) ?>">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <?= strtoupper(substr($profile['prenom'], 0, 1) . substr($profile['nom'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="info">
                                            <h6><?= htmlspecialchars($profile['prenom'] . ' ' . $profile['nom']) ?></h6>
                                            <p><?= htmlspecialchars($profile['titre_professionnel'] ?? $profile['titre_poste_recherche'] ?? ucfirst($profile['type_utilisateur'])) ?></p>
                                            <div class="rating">
                                                <?php
                                                $rating = $profile['note_prestataire'] ?? $profile['note_cv'] ?? 5;
                                                for ($i = 1; $i <= 5; $i++):
                                                    echo $i <= $rating ? '★' : '☆';
                                                endfor;
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="floating-card card-1">
                                <div class="card-content">
                                    <div class="avatar"><div class="avatar-placeholder">MD</div></div>
                                    <div class="info">
                                        <h6>Marie Dubois</h6>
                                        <p>Développeuse Web</p>
                                        <div class="rating">★★★★★</div>
                                    </div>
                                </div>
                            </div>
                            <div class="floating-card card-2">
                                <div class="card-content">
                                    <div class="avatar"><div class="avatar-placeholder">PM</div></div>
                                    <div class="info">
                                        <h6>Pierre Martin</h6>
                                        <p>Plombier Expert</p>
                                        <div class="rating">★★★★★</div>
                                    </div>
                                </div>
                            </div>
                            <div class="floating-card card-3">
                                <div class="card-content">
                                    <div class="avatar"><div class="avatar-placeholder">SL</div></div>
                                    <div class="info">
                                        <h6>Sophie Laurent</h6>
                                        <p>Designer UX/UI</p>
                                        <div class="rating">★★★★★</div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section py-5 bg-light" id="a-propos">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="section-title mb-4">À propos de <span class="text-primary">LULU-OPEN</span></h2>
                    <p class="lead mb-4">La plateforme qui révolutionne la mise en relation entre talents et opportunités.</p>
                    <p class="mb-3">LULU-OPEN est bien plus qu'une simple marketplace. C'est un écosystème complet où prestataires de services, candidats à l'emploi et recruteurs se rencontrent pour créer des collaborations fructueuses.</p>
                    <p class="mb-4">Notre mission : simplifier la recherche de talents et d'opportunités grâce à une plateforme intuitive, sécurisée et efficace.</p>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="about-stat">
                                <h3 class="text-primary mb-0"><?= number_format($stats['prestataires'] ?? 2500) ?>+</h3>
                                <p class="text-muted mb-0">Prestataires actifs</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="about-stat">
                                <h3 class="text-primary mb-0"><?= number_format($stats['cvs'] ?? 1200) ?>+</h3>
                                <p class="text-muted mb-0">Candidats qualifiés</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="about-features">
                        <div class="feature-item d-flex mb-4">
                            <div class="feature-icon-box me-3">
                                <i class="bi bi-shield-check"></i>
                            </div>
                            <div>
                                <h5>Profils vérifiés</h5>
                                <p class="text-muted mb-0">Tous nos membres sont vérifiés pour garantir la qualité et la sécurité.</p>
                            </div>
                        </div>
                        <div class="feature-item d-flex mb-4">
                            <div class="feature-icon-box me-3">
                                <i class="bi bi-lightning-charge"></i>
                            </div>
                            <div>
                                <h5>Mise en relation rapide</h5>
                                <p class="text-muted mb-0">Trouvez le bon profil en quelques clics grâce à notre moteur de recherche intelligent.</p>
                            </div>
                        </div>
                        <div class="feature-item d-flex">
                            <div class="feature-icon-box me-3">
                                <i class="bi bi-chat-heart"></i>
                            </div>
                            <div>
                                <h5>Communication facilitée</h5>
                                <p class="text-muted mb-0">Messagerie intégrée pour échanger directement avec les professionnels.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section py-5" id="categories">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5" data-aos="fade-up">
                    <h2 class="section-title">Explorez nos catégories de services</h2>
                    <p class="section-subtitle">Des professionnels qualifiés dans tous les domaines</p>
                </div>
            </div>
            
            <div class="row g-4" id="categoriesGrid">
                <?php if (!empty($categories)): ?>
                    <?php foreach (array_slice($categories, 0, 4) as $index => $category): ?>
                        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                            <a href="/lulu/search.php?type=prestataire&categories[]=<?= $category['id'] ?>" class="text-decoration-none">
                                <div class="category-card card-hover">
                                    <div class="category-icon" style="background: linear-gradient(135deg, <?= $category['couleur'] ?? '#0099FF' ?>, <?= $category['couleur'] ?? '#0099FF' ?>99)">
                                        <span style="font-size: 2rem;"><?= $category['icone'] ?? '📁' ?></span>
                                    </div>
                                    <h5><?= htmlspecialchars($category['nom']) ?></h5>
                                    <p class="text-muted"><?= $category['nb_prestataires'] ?? 0 ?> professionnels</p>
                                    <div class="category-arrow">
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">Aucune catégorie disponible pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($categories) && count($categories) > 4): ?>
                <div class="row mt-5">
                    <div class="col-12 text-center" data-aos="fade-up">
                        <a href="/lulu/services.php" class="btn btn-primary btn-lg px-5">
                            <i class="bi bi-grid-3x3-gap me-2"></i>Voir toutes les catégories
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Jobs Section -->
    <section class="jobs-section py-5 bg-light" id="emplois">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5" data-aos="fade-up">
                    <h2 class="section-title">Découvrez nos domaines d'emploi</h2>
                    <p class="section-subtitle">Des talents prêts à rejoindre votre équipe</p>
                </div>
            </div>
            
            <div class="row g-4">
                <?php if (!empty($categories)): ?>
                    <?php foreach (array_slice($categories, 0, 4) as $index => $category): ?>
                        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                            <a href="/lulu/search.php?type=candidat&categories[]=<?= $category['id'] ?>" class="text-decoration-none">
                                <div class="job-card card-hover">
                                    <div class="job-icon" style="background: linear-gradient(135deg, #FF3366, #FF6699)">
                                        <span style="font-size: 2rem;"><?= $category['icone'] ?? '💼' ?></span>
                                    </div>
                                    <h5><?= htmlspecialchars($category['nom']) ?></h5>
                                    <p class="text-muted">Candidats qualifiés</p>
                                    <div class="job-arrow">
                                        <i class="bi bi-arrow-right"></i>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($categories) && count($categories) > 4): ?>
                <div class="row mt-5">
                    <div class="col-12 text-center" data-aos="fade-up">
                        <a href="/lulu/emplois.php" class="btn btn-danger btn-lg px-5">
                            <i class="bi bi-briefcase me-2"></i>Voir tous les domaines d'emploi
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- How it Works Section -->
    <section class="how-it-works-section py-5 bg-light" id="comment-ca-marche">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5" data-aos="fade-up">
                    <h2 class="section-title">Comment ça marche ?</h2>
                    <p class="section-subtitle">Simple, rapide et efficace</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="step-card text-center">
                        <div class="step-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <h4>1. Recherchez</h4>
                        <p>Utilisez notre moteur de recherche avancé pour trouver le prestataire ou candidat idéal</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="step-card text-center">
                        <div class="step-icon">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <h4>2. Contactez</h4>
                        <p>Échangez directement avec les professionnels via notre messagerie intégrée</p>
                    </div>
                </div>
                
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="step-card text-center">
                        <div class="step-icon">
                            <i class="bi bi-star-fill"></i>
                        </div>
                        <h4>3. Évaluez</h4>
                        <p>Partagez votre expérience et aidez la communauté à faire les bons choix</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center" data-aos="fade-up">
                    <h2 class="cta-title mb-4">Prêt à rejoindre LULU-OPEN ?</h2>
                    <p class="cta-subtitle mb-5">Que vous soyez prestataire, candidat ou client, notre plateforme vous attend</p>
                    
                    <div class="cta-buttons">
                        <a href="/lulu/register?type=prestataire" class="btn btn-primary btn-lg me-3 mb-3">
                            Devenir Prestataire
                        </a>
                        <a href="/lulu/register?type=candidat" class="btn btn-outline-primary btn-lg mb-3">
                            Publier mon CV
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        .category-card {
            background: white;
            border-radius: 15px;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
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
            color: #000033;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .card-hover {
            cursor: pointer;
        }
        
        .floating-card .avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .floating-card .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .floating-card .avatar-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0099FF, #00CCFF);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        .floating-card .card-content {
            display: flex;
            align-items: center;
        }
        
        .floating-card .info {
            flex: 1;
        }
        
        .floating-card .info h6 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
        }
        
        .floating-card .info p {
            margin: 0 0 5px 0;
            font-size: 12px;
            color: #666;
        }
        
        .floating-card .rating {
            font-size: 12px;
            color: #ffc107;
        }
        
        .step-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0099FF, #00CCFF);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
            font-size: 2rem;
        }
        
        .about-stat h3 {
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .feature-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0099FF, #00CCFF);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .feature-item h5 {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .category-arrow, .job-arrow {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            opacity: 0;
            transition: all 0.3s ease;
            color: #0099FF;
            font-size: 1.2rem;
        }
        
        .category-card:hover .category-arrow,
        .job-card:hover .job-arrow {
            opacity: 1;
            transform: translateX(5px);
        }
        
        .job-card {
            background: white;
            border-radius: 15px;
            padding: 2rem 1.5rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: none;
            height: 100%;
            position: relative;
        }
        
        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .job-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: white;
        }
        
        .job-card h5 {
            color: #000033;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize AOS
            AOS.init({
                duration: 800,
                easing: 'ease-in-out',
                once: true
            });
            
            // Initialize category dropdown
            const categoryCheckboxes = document.querySelectorAll('.category-checkbox');
            const categoryText = document.getElementById('categoryText');
            
            categoryCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const selected = Array.from(categoryCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.nextElementSibling.textContent.trim());
                    
                    if (selected.length === 0) {
                        categoryText.textContent = 'Sélectionner catégories';
                    } else if (selected.length === 1) {
                        categoryText.textContent = selected[0];
                    } else {
                        categoryText.textContent = `${selected.length} catégories sélectionnées`;
                    }
                });
            });
        });
        
        // Function to search by category when clicking on category card
        function searchByCategory(categoryId, categoryName) {
            const form = document.querySelector('.search-form');
            const typeSelect = form.querySelector('select[name="type"]');
            
            // Set type to prestataire
            typeSelect.value = 'prestataire';
            
            // Uncheck all categories first
            document.querySelectorAll('.category-checkbox').forEach(cb => cb.checked = false);
            
            // Check the selected category
            const targetCheckbox = document.getElementById(`cat_${categoryId}`);
            if (targetCheckbox) {
                targetCheckbox.checked = true;
                // Update the dropdown text
                document.getElementById('categoryText').textContent = categoryName;
            }
            
            // Submit the form
            form.submit();
        }
        

    </script>
</body>
</html>
