<?php
require_once '../../config/config.php';
require_once '../../includes/i18n.php';
require_once '../../includes/theme-handler.php';
requireLogin();

if ($_SESSION['user_type'] !== 'prestataire_candidat') {
    redirect('../../index.php');
}

global $database;
$userId = $_SESSION['user_id'];

// Récupérer les paramètres utilisateur
$userSettings = $database->fetch("SELECT langue, devise FROM utilisateurs WHERE id = ?", [$userId]);
$langue = $userSettings['langue'] ?? detectBrowserLanguage();
$devise = $userSettings['devise'] ?? 'EUR';
$greeting = getGreeting($langue);

// Récupérer les profils
$prestataire = $database->fetch("SELECT * FROM profils_prestataires WHERE utilisateur_id = ?", [$userId]);
$candidat = $database->fetch("SELECT * FROM cvs WHERE utilisateur_id = ?", [$userId]);
$categories = $database->fetchAll("SELECT * FROM categories_services WHERE actif = 1 ORDER BY nom");

// Récupérer les catégories sélectionnées pour le prestataire
$selectedPrestataireCategories = [];
if ($prestataire) {
    $selectedPrestataireCategories = $database->fetchAll(
        "SELECT c.id, c.nom FROM categories_services c 
         INNER JOIN prestataire_categories pc ON c.id = pc.categorie_id 
         WHERE pc.prestataire_id = ?",
        [$prestataire['id']]
    );
}

// Récupérer les catégories sélectionnées pour le candidat
$selectedCandidatCategories = [];
if ($candidat) {
    $selectedCandidatCategories = $database->fetchAll(
        "SELECT c.id, c.nom FROM categories_services c 
         INNER JOIN cv_categories cc ON c.id = cc.categorie_id 
         WHERE cc.cv_id = ?",
        [$candidat['id']]
    );
}

$userSettings = getUserSettings();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($userSettings['langue'] ?? 'fr') ?>" data-theme="<?= htmlspecialchars($userSettings['theme'] ?? 'light') ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Prestataire & Candidat - LULU-OPEN</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        /* Styles consolidés */
        .waving-hand { display: inline-block; animation: wave 2s infinite; }
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(20deg); }
            75% { transform: rotate(-20deg); }
        }

        body { background: #f6f8fb; color: #222; }

        .admin-sidebar {
            width: 260px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            background: #fff;
            border-right: 1px solid #e6e9ef;
            padding: 1.5rem;
            overflow-y: auto;
        }
        .admin-content {
            margin-left: 280px;
            padding: 2rem;
            max-width: calc(100% - 300px);
        }

        .profile-photo-container { position: relative; display:inline-block; }
        .profile-photo { width: 96px; height: 96px; border-radius: 12px; object-fit: cover; }
        .profile-photo-placeholder { width: 96px; height: 96px; border-radius: 12px; display:flex; align-items:center; justify-content:center; background:#007bff; color:#fff; font-weight:700; font-size:1.5rem; }

        .profile-photo-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #0056b3;
            border: 2px solid white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .profile-photo-edit:hover { background-color: #003d80; }

        .welcome-text h1 { font-weight:700; font-size:2rem; color:#003366; margin:0 0 0.25rem 0; }
        .welcome-text p { margin:0; color:#555; }

        .mode-switcher { margin-bottom: 2rem; display:flex; justify-content:center; }
        .mode-switcher .btn-group { box-shadow: 0 4px 12px rgba(0,0,0,0.06); border-radius: 12px; overflow:hidden; }

        .stats-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:1.5rem; margin-bottom:2rem; }
        .stat-card { background:#fff; border-radius:12px; padding:1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.06); text-align:center; }
        .stat-card h3 { font-size:1.75rem; margin:0 0 0.25rem 0; color:#003366; }

        .profile-config-card { background:#fff; padding:2rem; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.06); }

        .portfolio-item img { width:100%; height:180px; object-fit:cover; border-radius:8px; }
        .nav-pills .nav-link { border-radius:12px; padding:0.75rem 1.5rem; font-weight:600; color:#003366; }
        .nav-pills .nav-link.active { background: linear-gradient(135deg,#003366,#007bff); color:#fff; }

        .form-label { font-weight:600; color:#003366; }
        .form-control, .form-select { border-radius:8px; padding:0.75rem 1rem; }
        .btn-primary, .btn-success { border-radius: 12px; padding:0.75rem 1.5rem; font-weight:600; }
    </style>
</head>
<body>
    <?php applyTheme(); ?>

    <div class="admin-sidebar">
        <div class="sidebar-header mb-4">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Dual</p>
        </div>
        <nav class="sidebar-nav d-flex flex-column gap-2">
            <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid me-2"></i> Dashboard</a>
            <a href="messages/inbox.php" class="nav-link"><i class="bi bi-chat-dots me-2"></i> Messages</a>
            <a href="abonnement.php" class="nav-link"><i class="bi bi-credit-card me-2"></i> Abonnement</a>
            <a href="settings.php" class="nav-link"><i class="bi bi-gear me-2"></i> Paramètres</a>
            <a href="../../logout.php" class="nav-link text-danger mt-3"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a>
        </nav>
    </div>

    <div class="admin-content">
        <?php
        $user = $database->fetch("SELECT statut FROM utilisateurs WHERE id = ?", [$userId]);
        $hasActiveSubscription = $database->fetch(
            "SELECT id FROM abonnements WHERE utilisateur_id = ? AND date_fin > NOW() AND statut = 'actif'",
            [$userId]
        );
        ?>
        <?php if ($user['statut'] === 'inactif' || !$hasActiveSubscription): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 2rem;"></i>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-2"><i class="bi bi-lock"></i> Compte inactif - Abonnement requis</h5>
                        <p class="mb-2">Votre profil n'est pas visible publiquement. Souscrivez à un abonnement pour activer votre compte et être visible par les clients.</p>
                        <a href="subscription/manage.php" class="btn btn-warning btn-sm"><i class="bi bi-credit-card"></i> Souscrire maintenant</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : htmlspecialchars($flashMessage['type']) ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php
        // Récupérer les informations utilisateur pour les messages d'onboarding
        $userInfo = $database->fetch("SELECT profil_complet FROM utilisateurs WHERE id = ?", [$userId]);
        $userModel = new User($database);
        $hasPaidSubscription = $userModel->hasActivePaidSubscription($userId);
        ?>

        <?php if (($userInfo['profil_complet'] ?? 0) == 0): ?>
            <div class="alert alert-warning alert-dismissible fade show mb-4" data-aos="fade-down">
                <div class="d-flex align-items-center">
                    <div class="alert-icon me-3">⚠️</div>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">Profil incomplet</h6>
                        <p class="mb-2">Votre profil n'est pas encore complet. Complétez-le pour être actif et visible sur la plateforme.</p>
                        <a href="profile/edit.php" class="btn btn-warning btn-sm">Compléter mon profil</a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$hasPaidSubscription): ?>
            <div class="alert alert-info alert-dismissible fade show mb-4" data-aos="fade-down" data-aos-delay="100">
                <div class="d-flex align-items-center">
                    <div class="alert-icon me-3">💡</div>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">Découvrez nos abonnements Premium</h6>
                        <p class="mb-2">Vous êtes actuellement sur le plan gratuit. Pour bénéficier d'une meilleure visibilité et de fonctionnalités avancées, découvrez nos abonnements payants.</p>
                        <a href="abonnement.php" class="btn btn-info btn-sm">Voir les abonnements</a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex align-items-center gap-4 mb-4">
            <div class="profile-photo-container">
                <?php
                $currentUser = $database->fetch("SELECT photo_profil FROM utilisateurs WHERE id = ?", [$userId]);
                if (!empty($currentUser['photo_profil'])):
                ?>
                    <img src="/lulu/uploads/<?= htmlspecialchars($currentUser['photo_profil']) ?>" alt="Photo de profil" class="profile-photo" id="profilePhotoPreview" />
                <?php else: ?>
                    <div class="profile-photo-placeholder" id="profilePhotoPreview"><?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 2)) ?></div>
                <?php endif; ?>
                <button class="profile-photo-edit" onclick="document.getElementById('profilePhotoInput').click()"><i class="bi bi-camera"></i></button>
                <input type="file" id="profilePhotoInput" accept="image/*" style="display:none" onchange="previewProfilePhoto(this)" />
            </div>

            <div class="welcome-text">
                <h1><?= htmlspecialchars($greeting . ' ' . ($_SESSION['user_name'] ?? '')) ?></h1>
                <p>Bienvenue sur votre tableau de bord. Gérez votre profil prestataire et candidat depuis cet espace.</p>
            </div>
        </div>

        <div class="mode-switcher mb-4">
            <div class="btn-group" role="group" aria-label="Mode switcher">
                <input type="radio" class="btn-check" name="mode" id="modePrestataire" value="prestataire" />
                <label class="btn btn-outline-primary" for="modePrestataire"><i class="bi bi-briefcase"></i> Mode Prestataire</label>

                <input type="radio" class="btn-check" name="mode" id="modeCandidat" value="candidat" />
                <label class="btn btn-outline-primary" for="modeCandidat"><i class="bi bi-file-person"></i> Mode Candidat</label>

                <input type="radio" class="btn-check" name="mode" id="modeCombined" value="combined" checked />
                <label class="btn btn-outline-primary" for="modeCombined"><i class="bi bi-grid-3x3"></i> Vue Combinée</label>
            </div>
        </div>

        <div class="stats-grid mb-4">
            <div class="stat-card">
                <i class="bi bi-eye" style="font-size:1.5rem; color:#007bff;"></i>
                <h3 id="profile-views">-</h3>
                <p>Vues profil prestataire</p>
            </div>
            <div class="stat-card">
                <i class="bi bi-star" style="font-size:1.5rem; color:#28a745;"></i>
                <h3 id="rating">-</h3>
                <p>Note moyenne</p>
            </div>
            <div class="stat-card">
                <i class="bi bi-file-earmark-text" style="font-size:1.5rem; color:#17a2b8;"></i>
                <h3 id="candidatures">-</h3>
                <p>Candidatures envoyées</p>
            </div>
            <div class="stat-card">
                <i class="bi bi-calendar-check" style="font-size:1.5rem; color:#ffc107;"></i>
                <h3 id="entretiens">-</h3>
                <p>Entretiens planifiés</p>
            </div>
        </div>

        <div class="profile-config-card">
            <ul class="nav nav-pills mb-4" role="tablist">
                <li class="nav-item me-2">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#prestataire" type="button" role="tab" aria-controls="prestataire" aria-selected="true">
                        <i class="bi bi-briefcase"></i> Profil Prestataire
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#candidat" type="button" role="tab" aria-controls="candidat" aria-selected="false">
                        <i class="bi bi-file-person"></i> Profil Candidat
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- PRESTATAIRE TAB -->
                <div class="tab-pane fade show active" id="prestataire" role="tabpanel">
                    <form method="POST" action="../../api/update-profile.php" novalidate>
                        <input type="hidden" name="profile_type" value="prestataire" />
                        <div class="mb-3">
                            <label for="titre_professionnel" class="form-label">Titre professionnel *</label>
                            <input type="text" class="form-control" id="titre_professionnel" name="titre_professionnel" value="<?= htmlspecialchars($prestataire['titre_professionnel'] ?? '') ?>" placeholder="Ex: Développeur Web Senior" required />
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="tarif_horaire" class="form-label">Tarif horaire (<?= htmlspecialchars($devise) ?>)</label>
                                <input type="number" class="form-control" id="tarif_horaire" name="tarif_horaire" step="0.01" value="<?= htmlspecialchars($prestataire['tarif_horaire'] ?? '') ?>" placeholder="50.00" />
                            </div>
                            <div class="col-md-6">
                                <label for="disponibilite" class="form-label">Disponibilité</label>
                                <select class="form-select" id="disponibilite" name="disponibilite">
                                    <option value="1" <?= ($prestataire['disponibilite'] ?? 1) == 1 ? 'selected' : '' ?>>Disponible</option>
                                    <option value="0" <?= ($prestataire['disponibilite'] ?? 1) == 0 ? 'selected' : '' ?>>Non disponible</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description_services" class="form-label">Description de vos services *</label>
                            <textarea class="form-control" id="description_services" name="description_services" rows="4" placeholder="Décrivez vos services et compétences..." required><?= htmlspecialchars($prestataire['description_services'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer le profil prestataire
                            </button>
                            <?php if ($prestataire): ?>
                                <button type="button" class="btn btn-danger" onclick="confirmDeleteProfile('prestataire')">
                                    <i class="bi bi-trash"></i> Supprimer le profil
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <hr />

                    <div class="mt-4">
                        <h5><i class="bi bi-images"></i> Mon Portfolio</h5>
                        <div id="portfolioList" class="row g-3"></div>
                        <button type="button" class="btn btn-outline-primary mt-3" data-bs-toggle="modal" data-bs-target="#addPortfolioModal">
                            <i class="bi bi-plus-circle"></i> Ajouter une réalisation
                        </button>
                    </div>

                    <!-- Portfolio Modal -->
                    <div class="modal fade" id="addPortfolioModal" tabindex="-1" aria-labelledby="addPortfolioModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addPortfolioModalLabel">Ajouter une réalisation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="addPortfolioForm">
                                        <div class="mb-3">
                                            <label for="portfolioTitle" class="form-label">Titre</label>
                                            <input type="text" class="form-control" id="portfolioTitle" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="portfolioDescription" class="form-label">Description</label>
                                            <textarea class="form-control" id="portfolioDescription" rows="3" required></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="portfolioImage" class="form-label">Image</label>
                                            <input type="file" class="form-control" id="portfolioImage" accept="image/*" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="portfolioLink" class="form-label">Lien</label>
                                            <input type="url" class="form-control" id="portfolioLink">
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    <button type="button" class="btn btn-primary" onclick="addPortfolioItem()">Enregistrer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CANDIDAT TAB -->
                <div class="tab-pane fade" id="candidat" role="tabpanel">
                    <form method="POST" action="../../api/update-profile.php" novalidate>
                        <input type="hidden" name="profile_type" value="candidat" />
                        <div class="mb-3">
                            <label for="titre_poste_recherche" class="form-label">Titre du poste recherché *</label>
                            <input type="text" class="form-control" id="titre_poste_recherche" name="titre_poste_recherche" value="<?= htmlspecialchars($candidat['titre_poste_recherche'] ?? '') ?>" placeholder="Ex: Développeur Full Stack" required />
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="type_contrat" class="form-label">Type de contrat</label>
                                <select class="form-select" id="type_contrat" name="type_contrat">
                                    <option value="CDI" <?= ($candidat['type_contrat'] ?? '') == 'CDI' ? 'selected' : '' ?>>CDI</option>
                                    <option value="CDD" <?= ($candidat['type_contrat'] ?? '') == 'CDD' ? 'selected' : '' ?>>CDD</option>
                                    <option value="Freelance" <?= ($candidat['type_contrat'] ?? '') == 'Freelance' ? 'selected' : '' ?>>Freelance</option>
                                    <option value="Stage" <?= ($candidat['type_contrat'] ?? '') == 'Stage' ? 'selected' : '' ?>>Stage</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="salaire_souhaite" class="form-label">Salaire souhaité (<?= htmlspecialchars($devise) ?>)</label>
                                <input type="number" class="form-control" id="salaire_souhaite" name="salaire_souhaite" step="0.01" value="<?= htmlspecialchars($candidat['salaire_souhaite'] ?? '') ?>" placeholder="Ex: 50000" />
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="competences" class="form-label">Compétences *</label>
                            <textarea class="form-control" id="competences" name="competences" rows="4" placeholder="Listez vos compétences..." required><?= htmlspecialchars($candidat['competences'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer le profil candidat
                            </button>
                            <?php if ($candidat): ?>
                                <button type="button" class="btn btn-danger" onclick="confirmDeleteProfile('candidat')">
                                    <i class="bi bi-trash"></i> Supprimer le profil
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div> <!-- /.tab-content -->
        </div> <!-- /.profile-config-card -->
    </div> <!-- /.admin-content -->

    <!-- Bootstrap JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Preview profile photo when a file is selected
    function previewProfilePhoto(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profilePhotoPreview');
            if (preview.tagName.toLowerCase() === 'img') {
                preview.src = e.target.result;
            } else {
                // replace placeholder div by an img
                const img = document.createElement('img');
                img.id = 'profilePhotoPreview';
                img.className = 'profile-photo';
                img.src = e.target.result;
                preview.replaceWith(img);
            }
        };
        reader.readAsDataURL(file);

        // Optionally: upload immediately via fetch to API endpoint to save profile photo
        // (left out: implement endpoint ../../api/upload-profile-photo.php)
    }

    // Confirm and delete profile (prestataire or candidat)
    async function confirmDeleteProfile(type) {
        if (!confirm('Confirmer la suppression du profil ' + type + ' ?')) return;
        try {
            const response = await fetch('../../api/delete-profile.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ profile_type: type })
            });
            const data = await response.json();
            if (data.success) {
                alert(data.message || 'Profil supprimé');
                window.location.reload();
            } else {
                alert(data.message || 'Erreur lors de la suppression');
            }
        } catch (err) {
            console.error(err);
            alert('Erreur réseau');
        }
    }

    // Delete a portfolio item
    async function deletePortfolio(id) {
        if (!confirm('Supprimer cette réalisation ?')) return;
        try {
            const response = await fetch('../../api/delete-portfolio.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id})
            });
            const data = await response.json();
            if (data.success) {
                loadPortfolio();
            } else {
                alert(data.message || 'Erreur');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur réseau');
        }
    }

    // Load portfolio items for this user
    async function loadPortfolio() {
        try {
            const response = await fetch("../../api/portfolio.php?user_id=<?= (int)$userId ?>");
            if (!response.ok) throw new Error('Network response not ok');
            const data = await response.json();

            const portfolioList = document.getElementById('portfolioList');
            if (!portfolioList) return;

            portfolioList.innerHTML = '';

            if (data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'col-md-4 mb-4';
                    div.innerHTML = `
                        <div class="card portfolio-item">
                            ${item.image ? `<img src="/lulu/uploads/portfolios/${item.image}" class="card-img-top" alt="${item.titre}">` : ''}
                            <div class="card-body">
                                <h5 class="card-title">${item.titre}</h5>
                                <p class="card-text">${item.description}</p>
                                <div class="d-flex justify-content-between mt-2">
                                    ${item.lien ? `<a href="${item.lien}" target="_blank" class="btn btn-sm btn-outline-primary">Voir</a>` : ''}
                                    <button class="btn btn-sm btn-danger" onclick="deletePortfolio(${item.id})">Supprimer</button>
                                </div>
                            </div>
                        </div>
                    `;
                    portfolioList.appendChild(div);
                });
            } else {
                portfolioList.innerHTML = '<div class="col-12">Aucune réalisation trouvée</div>';
            }
        } catch (error) {
            console.error('Erreur:', error);
            const portfolioList = document.getElementById('portfolioList');
            if (portfolioList) portfolioList.innerHTML = '<div class="col-12">Erreur de chargement des réalisations</div>';
        }
    }

    // Add a new portfolio item
    async function addPortfolioItem() {
        const title = document.getElementById('portfolioTitle').value.trim();
        const description = document.getElementById('portfolioDescription').value.trim();
        const imageInput = document.getElementById('portfolioImage');
        const link = document.getElementById('portfolioLink').value.trim();

        if (!title || !description || !imageInput.files[0]) {
            alert('Veuillez remplir tous les champs requis');
            return;
        }

        const formData = new FormData();
        formData.append('titre', title);
        formData.append('description', description);
        formData.append('image', imageInput.files[0]);
        formData.append('lien', link);

        try {
            const response = await fetch('../../api/add-portfolio.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                alert('Réalisation ajoutée avec succès');
                document.getElementById('addPortfolioForm').reset();
                const modalEl = document.getElementById('addPortfolioModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                // if no instance, try to hide via new Modal
                try { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); } catch (e) {}
                loadPortfolio();
            } else {
                alert(data.message || 'Erreur lors de l\'ajout.');
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur réseau lors de l\'ajout.');
        }
    }

    // Save active tab to localStorage and restore
    document.addEventListener('DOMContentLoaded', function() {
        // Load portfolio on start
        loadPortfolio();

        // tabs persistence
        const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabLinks.forEach(link => {
            link.addEventListener('click', function() {
                const tabId = this.getAttribute('data-bs-target');
                localStorage.setItem('activeTab', tabId);
            });
        });

        const activeTab = localStorage.getItem('activeTab');
        if (activeTab) {
            const tabLink = document.querySelector(`[data-bs-target="${activeTab}"]`);
            if (tabLink) {
                tabLink.click();
            }
        }
    });
    </script>
</body>
</html>
