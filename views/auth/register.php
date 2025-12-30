<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../includes/csrf.php';

$type = $_GET['type'] ?? 'client';
$step = $_GET['step'] ?? '1';

// Générer token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupération des catégories pour les prestataires/candidats
$categories = [];
try {
    global $database;
    $categories = $database->fetchAll("SELECT * FROM categories_services WHERE actif = 1 ORDER BY nom");
} catch (Exception $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Left Side - Form -->
            <div class="col-lg-6 d-flex align-items-center justify-content-center">
                <div class="auth-form-container">
                    <div class="text-center mb-4">
                        <h1 class="auth-title">
                            <span class="text-primary">LULU</span><span class="text-dark">-OPEN</span>
                        </h1>
                        <p class="auth-subtitle">Rejoignez notre communauté</p>
                    </div>
                    
                    <?php if (isset($_SESSION['flash_message'])): ?>
                        <div class="alert alert-<?= $_SESSION['flash_message']['type'] === 'error' ? 'danger' : $_SESSION['flash_message']['type'] ?> alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['flash_message']['message']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash_message']); ?>
                    <?php endif; ?>

                    <!-- Progress Steps -->
                    <div class="progress-steps mb-4">
                        <div class="step <?= $step >= 1 ? 'active' : '' ?>">
                            <div class="step-number">1</div>
                            <div class="step-label">Type de compte</div>
                        </div>
                        <div class="step <?= $step >= 2 ? 'active' : '' ?>">
                            <div class="step-number">2</div>
                            <div class="step-label">Informations</div>
                        </div>
                        <div class="step <?= $step >= 3 ? 'active' : '' ?>">
                            <div class="step-number">3</div>
                            <div class="step-label">Profil</div>
                        </div>
                    </div>

                    <?php if ($step == '1'): ?>
                        <!-- Step 1: Account Type Selection -->
                        <form id="typeSelectionForm" method="GET">
                            <input type="hidden" name="step" value="2">
                            
                            <h3 class="mb-4">Quel type de compte souhaitez-vous créer ?</h3>
                            
                            <div class="account-types">
                                <div class="account-type-card" data-type="client">
                                    <div class="account-icon">👤</div>
                                    <h5>Client</h5>
                                    <p>Je recherche des services ou des candidats</p>
                                    <div class="account-features">
                                        <span class="feature">✓ Recherche illimitée</span>
                                        <span class="feature">✓ Contact direct</span>
                                        <span class="feature">✓ Avis et notes</span>
                                    </div>
                                </div>

                                <div class="account-type-card" data-type="professionnel">
                                    <div class="account-icon">💼</div>
                                    <h5>Professionnel</h5>
                                    <p>Je propose mes services ou recherche un emploi</p>
                                    <div class="account-features">
                                        <span class="feature">✓ Prestataire de services</span>
                                        <span class="feature">✓ Candidat à l'emploi</span>
                                        <span class="feature">✓ Ou les deux</span>
                                    </div>
                                </div>
                            </div>

                            <input type="hidden" name="type" id="selectedType" value="<?= $type ?>">
                            
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" id="continueBtn" disabled>
                                    Continuer <i class="bi bi-arrow-right"></i>
                                </button>
                            </div>
                        </form>

                    <?php elseif ($step == '2'): ?>
                        <!-- Step 2: Personal Information -->
                        <form id="personalInfoForm" method="POST" action="../../auth-handler.php" onsubmit="console.log('Formulaire soumis'); return true;">
                            <input type="hidden" name="action" value="register">
                            <input type="hidden" name="type_utilisateur" id="typeUtilisateur" value="<?= htmlspecialchars($type) ?>">
                            <input type="hidden" name="step" value="2">
                            <?php echo csrf_field(); ?>
                            
                            <h3 class="mb-4">Informations personnelles</h3>
                            
                            <?php if ($type === 'professionnel'): ?>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Type de profil professionnel *</label>
                                <div class="profile-type-selection">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="isPrestataire" name="is_prestataire" value="1">
                                        <label class="form-check-label" for="isPrestataire">
                                            <strong>Prestataire de services</strong>
                                            <small class="d-block text-muted">Je propose mes services professionnels</small>
                                        </label>
                                    </div>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="isCandidat" name="is_candidat" value="1">
                                        <label class="form-check-label" for="isCandidat">
                                            <strong>Candidat à l'emploi</strong>
                                            <small class="d-block text-muted">Je recherche un emploi</small>
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted">Vous pouvez sélectionner les deux options</small>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">Catégories de services *</label>
                                <div class="custom-multiselect">
                                    <div class="multiselect-header" id="multiselectHeader">
                                        <span id="selectedText">Sélectionnez jusqu'à 3 catégories</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </div>
                                    <div class="multiselect-options" id="multiselectOptions" style="display: none;">
                                        <?php foreach ($categories as $category): ?>
                                            <label class="multiselect-option">
                                                <input type="checkbox" name="categories[]" value="<?= $category['id'] ?>" class="category-check">
                                                <span><?= htmlspecialchars($category['nom']) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <small class="text-danger" id="categoryError" style="display: none;">Maximum 3 catégories</small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" name="prenom" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nom *</label>
                                    <input type="text" class="form-control" name="nom" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="phoneInput" name="telephone">
                            </div>

                            <!-- Location with Country/City Selection -->
                            <div class="mb-3">
                                <label class="form-label">Pays *</label>
                                <select class="form-select" id="countrySelect" name="pays" required>
                                    <option value="">Sélectionnez votre pays</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Ville *</label>
                                <select class="form-select" id="citySelect" name="ville" required disabled>
                                    <option value="">Sélectionnez d'abord un pays</option>
                                </select>
                                <input type="hidden" name="code_iso" id="selectedCodeIso">
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Mot de passe *</label>
                                    <div class="password-input">
                                        <input type="password" class="form-control" name="password" id="password" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirmer le mot de passe *</label>
                                    <div class="password-input">
                                        <input type="password" class="form-control" name="confirm_password" id="confirmPassword" required>
                                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="acceptTerms" required>
                                <label class="form-check-label" for="acceptTerms">
                                    J'accepte les <a href="#" class="text-primary">conditions d'utilisation</a> 
                                    et la <a href="#" class="text-primary">politique de confidentialité</a>
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <?php if ($type === 'client'): ?>
                                        Créer mon compte
                                    <?php else: ?>
                                        Continuer vers le profil <i class="bi bi-arrow-right"></i>
                                    <?php endif; ?>
                                </button>
                            </div>
                        </form>

                    <?php elseif ($step == '3'): ?>
                        <!-- Step 3: Profile Setup -->
                        <form id="profileSetupForm" method="POST" action="../../auth-handler.php" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="setup_profile">
                            <input type="hidden" name="type_utilisateur" value="<?= htmlspecialchars($type) ?>">
                            <?php echo csrf_field(); ?>
                            
                            <h3 class="mb-4">Configuration du profil</h3>
                            
                            <?php if (in_array($type, ['prestataire', 'prestataire_candidat'])): ?>
                                <!-- Prestataire Profile -->
                                <div class="profile-section mb-4">
                                    <h5 class="text-primary mb-3">
                                        <i class="bi bi-briefcase"></i> Profil Prestataire
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Catégorie de service *</label>
                                        <select class="form-select" name="prestataire_categorie_id" required>
                                            <option value="">Choisir une catégorie</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>">
                                                    <?= htmlspecialchars($category['nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Titre professionnel *</label>
                                        <input type="text" class="form-control" name="titre_professionnel" 
                                               placeholder="Ex: Développeur Web Senior" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description de vos services *</label>
                                        <textarea class="form-control" name="description_services" rows="4" 
                                                  placeholder="Décrivez vos services et compétences..." required></textarea>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Tarif horaire (€)</label>
                                            <input type="number" class="form-control" name="tarif_horaire" 
                                                   min="0" step="0.01" placeholder="50.00">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Années d'expérience</label>
                                            <select class="form-select" name="experience_annees">
                                                <option value="0">Débutant</option>
                                                <option value="1">1 an</option>
                                                <option value="2">2 ans</option>
                                                <option value="3">3 ans</option>
                                                <option value="5">5+ ans</option>
                                                <option value="10">10+ ans</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (in_array($type, ['candidat', 'prestataire_candidat'])): ?>
                                <!-- Candidat Profile -->
                                <div class="profile-section mb-4">
                                    <h5 class="text-success mb-3">
                                        <i class="bi bi-file-person"></i> Profil Candidat
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Domaine de recherche *</label>
                                        <select class="form-select" name="candidat_categorie_id" required>
                                            <option value="">Choisir un domaine</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>">
                                                    <?= htmlspecialchars($category['nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Poste recherché *</label>
                                        <input type="text" class="form-control" name="titre_poste_recherche" 
                                               placeholder="Ex: Développeur Full Stack" required>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Niveau d'expérience *</label>
                                            <select class="form-select" name="niveau_experience" required>
                                                <option value="">Choisir</option>
                                                <option value="debutant">Débutant</option>
                                                <option value="junior">Junior (1-3 ans)</option>
                                                <option value="confirme">Confirmé (3-7 ans)</option>
                                                <option value="senior">Senior (7+ ans)</option>
                                                <option value="expert">Expert (10+ ans)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Salaire souhaité (€/an)</label>
                                            <input type="number" class="form-control" name="salaire_souhaite" 
                                                   min="0" placeholder="45000">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Type de contrat recherché *</label>
                                        <select class="form-select" name="type_contrat" required>
                                            <option value="">Choisir</option>
                                            <option value="cdi">CDI</option>
                                            <option value="cdd">CDD</option>
                                            <option value="freelance">Freelance</option>
                                            <option value="stage">Stage</option>
                                            <option value="alternance">Alternance</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Compétences principales *</label>
                                        <textarea class="form-control" name="competences" rows="3" 
                                                  placeholder="Ex: PHP, JavaScript, MySQL, HTML, CSS..." required></textarea>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    Finaliser mon inscription <i class="bi bi-check-circle"></i>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="text-center mt-4">
                        <p class="text-muted">
                            Déjà inscrit ? 
                            <a href="/lulu/login.php" class="text-primary text-decoration-none">Se connecter</a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Right Side - Visual -->
            <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center auth-visual">
                <div class="text-center text-white">
                    <h2 class="mb-4">Rejoignez LULU-OPEN</h2>
                    <p class="lead mb-4">La marketplace qui connecte les talents avec les opportunités</p>
                    
                    <div class="features-list">
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Inscription gratuite</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Profil professionnel</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Messagerie intégrée</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span>Système d'avis</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Désactiver l'API phone temporairement pour éviter l'erreur 404
    console.log('Script inline chargé');
    </script>
    <script src="../../assets/js/phone-simple.js?v=<?= time() ?>"></script>
    
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
            background: var(--light-gray);
        }

        .auth-visual {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-blue));
        }

        .auth-form-container {
            max-width: 500px;
            width: 100%;
            padding: 2rem;
        }

        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 60%;
            right: -40%;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }

        .step.active:not(:last-child)::after {
            background: var(--primary-blue);
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }

        .step.active .step-number {
            background: var(--primary-blue);
        }

        .step-label {
            font-size: 0.8rem;
            color: var(--medium-gray);
            text-align: center;
        }

        .step.active .step-label {
            color: var(--primary-blue);
            font-weight: 600;
        }

        .account-types {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .account-type-card {
            border: 2px solid #dee2e6;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: white;
        }

        .account-type-card:hover {
            border-color: var(--primary-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .account-type-card.selected {
            border-color: var(--primary-blue);
            background: rgba(0, 153, 255, 0.05);
        }

        .account-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .account-type-card h5 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .account-type-card p {
            color: var(--medium-gray);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .account-features {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .feature {
            font-size: 0.8rem;
            color: var(--success-green);
        }

        .location-input-container {
            position: relative;
        }

        .location-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .location-suggestion {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
        }

        .location-suggestion:hover {
            background: var(--light-gray);
        }

        .location-suggestion:last-child {
            border-bottom: none;
        }

        .password-input {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--medium-gray);
            cursor: pointer;
        }

        .profile-section {
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            background: white;
        }
        
        .profile-type-selection {
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            padding: 1rem;
            background: #f8f9fa;
        }
        
        .profile-type-selection .form-check {
            padding: 0.5rem;
        }
        
        .profile-type-selection .form-check-label {
            cursor: pointer;
        }
        
        .custom-multiselect {
            position: relative;
        }
        
        .multiselect-header {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .multiselect-header:hover {
            border-color: var(--primary-blue);
        }
        
        .multiselect-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-top: 4px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .multiselect-option {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .multiselect-option:hover {
            background: #f8f9fa;
        }
        
        .multiselect-option input {
            margin-right: 0.75rem;
        }

        .features-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .account-types {
                grid-template-columns: 1fr;
            }
            
            .auth-form-container {
                padding: 1rem;
            }
        }
    </style>

    <script>
        // Account type selection
        document.querySelectorAll('.account-type-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.account-type-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                
                const type = this.dataset.type;
                document.getElementById('selectedType').value = type;
                document.getElementById('continueBtn').disabled = false;
            });
        });
        
        // Update type based on professional profile selection
        const isPrestataireCheckbox = document.getElementById('isPrestataire');
        const isCandidatCheckbox = document.getElementById('isCandidat');
        const typeUtilisateurInput = document.getElementById('typeUtilisateur');
        
        if (isPrestataireCheckbox && isCandidatCheckbox) {
            function updateUserType() {
                const isPrestataire = isPrestataireCheckbox.checked;
                const isCandidat = isCandidatCheckbox.checked;
                
                if (isPrestataire && isCandidat) {
                    typeUtilisateurInput.value = 'prestataire_candidat';
                } else if (isPrestataire) {
                    typeUtilisateurInput.value = 'prestataire';
                } else if (isCandidat) {
                    typeUtilisateurInput.value = 'candidat';
                } else {
                    typeUtilisateurInput.value = 'professionnel';
                }
            }
            
            isPrestataireCheckbox.addEventListener('change', updateUserType);
            isCandidatCheckbox.addEventListener('change', updateUserType);
        }

        // Country/City selection
        const countrySelect = document.getElementById('countrySelect');
        const citySelect = document.getElementById('citySelect');
        
        // Load countries on page load
        if (countrySelect) {
            fetch('../../api/countries-cities.php?action=countries')
                .then(response => response.json())
                .then(countries => {
                    countries.forEach(country => {
                        const option = document.createElement('option');
                        option.value = country.pays;
                        option.textContent = country.pays;
                        option.dataset.codeIso = country.code_iso;
                        countrySelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Erreur chargement pays:', error));
            
            // Load cities when country changes
            countrySelect.addEventListener('change', function() {
                const selectedCountry = this.value;
                citySelect.innerHTML = '<option value="">Chargement...</option>';
                citySelect.disabled = true;
                
                if (selectedCountry) {
                    fetch(`../../api/countries-cities.php?action=cities&country=${encodeURIComponent(selectedCountry)}`)
                        .then(response => response.json())
                        .then(cities => {
                            citySelect.innerHTML = '<option value="">Sélectionnez votre ville</option>';
                            cities.forEach(city => {
                                const option = document.createElement('option');
                                option.value = city.ville;
                                option.textContent = city.ville + (city.region ? ` (${city.region})` : '');
                                option.dataset.codeIso = city.code_iso;
                                citySelect.appendChild(option);
                            });
                            citySelect.disabled = false;
                        })
                        .catch(error => {
                            console.error('Erreur chargement villes:', error);
                            citySelect.innerHTML = '<option value="">Erreur de chargement</option>';
                        });
                } else {
                    citySelect.innerHTML = '<option value="">Sélectionnez d\'abord un pays</option>';
                }
            });
            
            // Update code ISO when city changes
            citySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.dataset.codeIso) {
                    document.getElementById('selectedCodeIso').value = selectedOption.dataset.codeIso;
                }
            });
        }

        // Password toggle
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.nextElementSibling.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion de la liste déroulante personnalisée
            const multiselectHeader = document.getElementById('multiselectHeader');
            const multiselectOptions = document.getElementById('multiselectOptions');
            const selectedText = document.getElementById('selectedText');
            const categoryChecks = document.querySelectorAll('.category-check');
            const categoryError = document.getElementById('categoryError');
            
            // Ouvrir/fermer la liste
            if (multiselectHeader) {
                multiselectHeader.addEventListener('click', function(e) {
                    e.stopPropagation();
                    multiselectOptions.style.display = multiselectOptions.style.display === 'none' ? 'block' : 'none';
                });
                
                // Fermer si clic en dehors
                document.addEventListener('click', function() {
                    multiselectOptions.style.display = 'none';
                });
                
                multiselectOptions.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
            
            // Gérer la sélection des catégories
            categoryChecks.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const checked = document.querySelectorAll('.category-check:checked');
                    
                    if (checked.length > 3) {
                        this.checked = false;
                        categoryError.style.display = 'block';
                        setTimeout(() => {
                            categoryError.style.display = 'none';
                        }, 3000);
                        return;
                    }
                    
                    // Mettre à jour le texte
                    if (checked.length === 0) {
                        selectedText.textContent = 'Sélectionnez jusqu\'\u00e0 3 catégories';
                    } else {
                        const names = Array.from(checked).map(c => c.nextElementSibling.textContent);
                        selectedText.textContent = names.join(', ');
                    }
                });
            });
            
            const personalInfoForm = document.getElementById('personalInfoForm');
            if (personalInfoForm) {
                personalInfoForm.addEventListener('submit', function(e) {
                    // Validation personnalisée pour les professionnels
                    const typeUtilisateur = document.getElementById('typeUtilisateur');
                    if (typeUtilisateur && typeUtilisateur.value === 'professionnel') {
                        const isPrestataire = document.getElementById('isPrestataire');
                        const isCandidat = document.getElementById('isCandidat');
                        if (!isPrestataire.checked && !isCandidat.checked) {
                            e.preventDefault();
                            alert('Veuillez sélectionner au moins un type de profil professionnel');
                            return false;
                        }
                        
                        // Vérifier qu'au moins une catégorie est sélectionnée
                        const checkedCategories = document.querySelectorAll('.category-check:checked');
                        if (checkedCategories.length === 0) {
                            e.preventDefault();
                            alert('Veuillez sélectionner au moins une catégorie');
                            return false;
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>