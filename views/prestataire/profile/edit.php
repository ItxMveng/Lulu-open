<?php
require_once '../../../config/config.php';
requireLogin();

if (!in_array($_SESSION['user_type'], ['prestataire', 'candidat'])) {
    redirect('../../../index.php');
}

/**
 * Vérifie si le profil d'un utilisateur est complet
 */
function checkProfileCompletion($userId, $userType, $updateData = []) {
    global $database;

    // Récupérer les données utilisateur mises à jour
    $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$userId]);

    // Appliquer les données mises à jour si fournies
    if (!empty($updateData)) {
        foreach ($updateData as $key => $value) {
            if (isset($user[$key])) {
                $user[$key] = $value;
            }
        }
    }

    // Champs obligatoires pour tous les types
    if (empty($user['prenom']) || empty($user['nom']) || empty($user['photo_profil'])) {
        return false;
    }

    // Vérifier les profils selon le type d'utilisateur
    if (in_array($userType, ['prestataire', 'prestataire_candidat'])) {
        $profile = $database->fetch("SELECT * FROM profils_prestataires WHERE utilisateur_id = ?", [$userId]);
        if (!$profile) {
            return false;
        }

        // Appliquer les données POST si disponibles
        if (isset($_POST['titre_professionnel'])) {
            $profile['titre_professionnel'] = $_POST['titre_professionnel'];
        }
        if (isset($_POST['description_services'])) {
            $profile['description_services'] = $_POST['description_services'];
        }
        if (isset($_POST['categorie_id'])) {
            $profile['categorie_id'] = $_POST['categorie_id'];
        }

        // Champs obligatoires pour prestataire
        if (empty($profile['titre_professionnel']) ||
            empty($profile['description_services']) ||
            empty($profile['categorie_id'])) {
            return false;
        }
    }

    if (in_array($userType, ['candidat', 'prestataire_candidat'])) {
        $cv = $database->fetch("SELECT * FROM cvs WHERE utilisateur_id = ?", [$userId]);
        if (!$cv) {
            return false;
        }

        // Appliquer les données POST si disponibles
        if (isset($_POST['titre_poste_recherche'])) {
            $cv['titre_poste_recherche'] = $_POST['titre_poste_recherche'];
        }
        if (isset($_POST['competences'])) {
            $cv['competences'] = $_POST['competences'];
        }
        if (isset($_POST['categorie_id'])) {
            $cv['categorie_id'] = $_POST['categorie_id'];
        }

        // Champs obligatoires pour candidat
        if (empty($cv['titre_poste_recherche']) ||
            empty($cv['competences']) ||
            empty($cv['categorie_id'])) {
            return false;
        }
    }

    return true;
}

global $database;
$userId = $_SESSION['user_id'];

// Récupération des données utilisateur
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = :id", ['id' => $userId]);
$profile = null;

if ($_SESSION['user_type'] === 'prestataire') {
    $profile = $database->fetch("SELECT * FROM profils_prestataires WHERE utilisateur_id = :id", ['id' => $userId]);
} elseif ($_SESSION['user_type'] === 'candidat') {
    $profile = $database->fetch("SELECT * FROM cvs WHERE utilisateur_id = :id", ['id' => $userId]);
}

// Récupération des catégories
$categories = $database->fetchAll("SELECT * FROM categories_services WHERE actif = 1 ORDER BY nom");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updateData = [
            'nom' => $_POST['nom'],
            'prenom' => $_POST['prenom'],
            'telephone' => $_POST['telephone']
        ];
        
        // Upload photo de profil
        if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
            $photoPath = uploadFile($_FILES['photo_profil'], 'profiles');
            if ($photoPath) {
                if ($user['photo_profil']) {
                    deleteFile($user['photo_profil']);
                }
                $updateData['photo_profil'] = $photoPath;
            } else {
                throw new Exception('Erreur lors du téléchargement de la photo de profil');
            }
        }
        
        $database->update('utilisateurs', $updateData, 'id = ?', [$userId]);

        // Mise à jour du profil prestataire
        if ($_SESSION['user_type'] === 'prestataire') {
            $profileData = [
                'titre_professionnel' => $_POST['titre_professionnel'],
                'description_services' => $_POST['description_services'],
                'tarif_horaire' => $_POST['tarif_horaire'],
                'categorie_id' => $_POST['categorie_id'],
                'experience_annees' => $_POST['experience_annees']
            ];

            if ($profile) {
                $database->update('profils_prestataires', $profileData, 'utilisateur_id = ?', [$userId]);
            } else {
                $profileData['utilisateur_id'] = $userId;
                $database->insert('profils_prestataires', $profileData);
            }
        }

        // Vérifier si le profil est maintenant complet
        $isProfileComplete = checkProfileCompletion($userId, $_SESSION['user_type'], $updateData);

        // Mettre à jour le statut de complétion du profil
        $database->update('utilisateurs', ['profil_complet' => $isProfileComplete ? 1 : 0], 'id = ?', [$userId]);

        // Si le profil est complet et que le statut est en_attente, passer à actif
        if ($isProfileComplete && $user['statut'] === 'en_attente') {
            $database->update('utilisateurs', ['statut' => 'actif'], 'id = ?', [$userId]);

            // Envoyer une notification de félicitations
            $database->insert('messages', [
                'expediteur_id' => 1, // Admin
                'destinataire_id' => $userId,
                'sujet' => 'Félicitations ! Votre profil est maintenant complet',
                'contenu' => "Bravo {$user['prenom']} !\n\nVotre profil est maintenant complet et votre compte est actif sur LULU-OPEN. Vous êtes désormais visible par les clients potentiels.\n\nContinuez à optimiser votre profil en ajoutant des photos de vos réalisations et en répondant rapidement aux messages.\n\nL'équipe LULU-OPEN",
                'lu' => 0,
                'date_envoi' => date('Y-m-d H:i:s')
            ]);
        }

        flashMessage('Profil mis à jour avec succès', 'success');
        redirect('views/prestataire/profile/edit.php');
        
    } catch (Exception $e) {
        flashMessage($e->getMessage(), 'error');
    }
}

// Récupération du portfolio
$portfolioItems = [];
if ($_SESSION['user_type'] === 'prestataire' && $profile) {
    $portfolioData = $profile['portfolio_images'] ? json_decode($profile['portfolio_images'], true) : [];
    $portfolioItems = is_array($portfolioData) ? $portfolioData : [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../../assets/css/main.css" rel="stylesheet">
</head>
<body class="admin-page">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Prestataire</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="../dashboard.php" class="nav-link">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="edit.php" class="nav-link active">
                <i class="icon">✏️</i> Mon Profil
            </a>
            <a href="../messages/inbox.php" class="nav-link">
                <i class="icon">💬</i> Messages
            </a>
            
            <a href="../abonnement.php" class="nav-link">
                <i class="icon">💳</i> Abonnement
            </a>
            <a href="../../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="content-header">
            <h1>Modifier mon profil</h1>
            <p class="text-muted">Mettez à jour vos informations professionnelles</p>
        </div>

        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row g-4">
                <!-- Photo de profil -->
                <div class="col-12">
                    <div class="admin-card">
                        <div class="card-header">
                            <h5>Photo de profil</h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="profile-photo-container mb-3">
                                <?php if ($user['photo_profil']): ?>
                                    <img src="../../../uploads/<?= $user['photo_profil'] ?>" class="profile-photo" id="profilePreview">
                                <?php else: ?>
                                    <div class="profile-photo-placeholder" id="profilePreview">
                                        <i class="icon">📷</i>
                                        <p>Aucune photo</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="file" class="form-control" name="photo_profil" accept="image/*" id="photoInput">
                            <small class="text-muted">Formats acceptés: JPG, PNG, GIF (max 5MB)</small>
                        </div>
                    </div>
                </div>
                
                <!-- Informations personnelles -->
                <div class="col-lg-6">
                    <div class="admin-card">
                        <div class="card-header">
                            <h5>Informations personnelles</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Prénom *</label>
                                <input type="text" class="form-control" name="prenom" 
                                       value="<?= htmlspecialchars($user['prenom']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nom *</label>
                                <input type="text" class="form-control" name="nom" 
                                       value="<?= htmlspecialchars($user['nom']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="telephone" 
                                       value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                                <small class="text-muted">L'email ne peut pas être modifié</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations professionnelles -->
                <?php if ($_SESSION['user_type'] === 'prestataire'): ?>
                <div class="col-lg-6">
                    <div class="admin-card">
                        <div class="card-header">
                            <h5>Informations professionnelles</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Titre professionnel *</label>
                                <input type="text" class="form-control" name="titre_professionnel" 
                                       value="<?= htmlspecialchars($profile['titre_professionnel'] ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catégorie *</label>
                                <select class="form-select" name="categorie_id" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" 
                                                <?= ($profile['categorie_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Tarif horaire (€)</label>
                                <input type="number" class="form-control" name="tarif_horaire" step="0.01"
                                       value="<?= $profile['tarif_horaire'] ?? '' ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Années d'expérience</label>
                                <input type="number" class="form-control" name="experience_annees"
                                       value="<?= $profile['experience_annees'] ?? '' ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="admin-card">
                        <div class="card-header">
                            <h5>Description des services</h5>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" name="description_services" rows="5"
                                      placeholder="Décrivez vos services, compétences et expériences..."><?= htmlspecialchars($profile['description_services'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Portfolio -->
                <div class="col-12">
                    <div class="admin-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Portfolio</h5>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addPortfolioItem()">Ajouter</button>
                        </div>
                        <div class="card-body">
                            <div id="portfolioContainer">
                                <?php if (empty($portfolioItems)): ?>
                                    <p class="text-muted text-center">Aucun élément dans votre portfolio. Ajoutez vos réalisations pour attirer plus de clients !</p>
                                <?php else: ?>
                                    <?php foreach ($portfolioItems as $index => $item): ?>
                                        <div class="portfolio-item" data-index="<?= $index ?>">
                                            <div class="row align-items-center">
                                                <div class="col-md-3">
                                                    <?php if ($item['type'] === 'image'): ?>
                                                        <img src="../../../uploads/<?= $item['url'] ?>" class="portfolio-thumb">
                                                    <?php else: ?>
                                                        <div class="portfolio-link-preview">
                                                            <i class="icon">🔗</i>
                                                            <span>Lien</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-7">
                                                    <h6><?= htmlspecialchars($item['title']) ?></h6>
                                                    <p class="text-muted mb-0"><?= htmlspecialchars($item['description']) ?></p>
                                                </div>
                                                <div class="col-md-2 text-end">
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="removePortfolioItem(<?= $index ?>)">Supprimer</button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="../dashboard.php" class="btn btn-secondary">Retour au dashboard</a>
            </div>
        </form>
    </div>

    <!-- Portfolio Modal -->
    <div class="modal fade" id="portfolioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter au portfolio</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="portfolioForm">
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="portfolioType" onchange="togglePortfolioFields()">
                                <option value="image">Image</option>
                                <option value="link">Lien</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" id="portfolioTitle" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="portfolioDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3" id="imageField">
                            <label class="form-label">Image</label>
                            <input type="file" class="form-control" id="portfolioImage" accept="image/*">
                        </div>
                        <div class="mb-3" id="linkField" style="display: none;">
                            <label class="form-label">URL</label>
                            <input type="url" class="form-control" id="portfolioUrl">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" onclick="savePortfolioItem()">Ajouter</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let portfolioItems = <?= json_encode($portfolioItems) ?>;
        
        // Preview photo de profil
        document.getElementById('photoInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('profilePreview');
                    preview.innerHTML = `<img src="${e.target.result}" class="profile-photo">`;
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Portfolio functions
        function addPortfolioItem() {
            const modal = new bootstrap.Modal(document.getElementById('portfolioModal'));
            document.getElementById('portfolioForm').reset();
            togglePortfolioFields();
            modal.show();
        }
        
        function togglePortfolioFields() {
            const type = document.getElementById('portfolioType').value;
            document.getElementById('imageField').style.display = type === 'image' ? 'block' : 'none';
            document.getElementById('linkField').style.display = type === 'link' ? 'block' : 'none';
        }
        
        async function savePortfolioItem() {
            const type = document.getElementById('portfolioType').value;
            const title = document.getElementById('portfolioTitle').value;
            const description = document.getElementById('portfolioDescription').value;
            
            if (!title) {
                alert('Le titre est requis');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'add_portfolio');
            formData.append('type', type);
            formData.append('title', title);
            formData.append('description', description);
            
            if (type === 'image') {
                const imageFile = document.getElementById('portfolioImage').files[0];
                if (!imageFile) {
                    alert('Veuillez sélectionner une image');
                    return;
                }
                formData.append('image', imageFile);
            } else {
                const url = document.getElementById('portfolioUrl').value;
                if (!url) {
                    alert('Veuillez saisir une URL');
                    return;
                }
                formData.append('url', url);
            }
            
            try {
                const response = await fetch('/lulu/api/portfolio.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                alert('Erreur de connexion');
            }
        }
        
        async function removePortfolioItem(index) {
            if (!confirm('Supprimer cet élément du portfolio ?')) return;
            
            try {
                const response = await fetch('/lulu/api/portfolio.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        action: 'remove_portfolio',
                        index: index
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                alert('Erreur de connexion');
            }
        }
    </script>
    
    <style>
        :root {
            --primary-color: #0099FF;
            --primary-dark: #000033;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --border-radius-lg: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 25px rgba(0, 153, 255, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            --font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-page {
            background: #f8f9fa;
            font-family: var(--font-family);
        }
        
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-nav .icon {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .admin-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .card-header {
            background: var(--light-gray);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .card-header h5 {
            margin-bottom: 0;
            color: var(--primary-dark);
        }
        
        .profile-photo-container {
            position: relative;
            display: inline-block;
        }
        
        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--primary-color);
        }
        
        .profile-photo-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: var(--light-gray);
            border: 2px dashed var(--medium-gray);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--medium-gray);
        }
        
        .profile-photo-placeholder .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .portfolio-item {
            padding: 1rem;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }
        
        .portfolio-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--border-radius);
        }
        
        .portfolio-link-preview {
            width: 80px;
            height: 80px;
            background: var(--light-gray);
            border-radius: var(--border-radius);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--medium-gray);
        }
        
        .portfolio-link-preview .icon {
            font-size: 1.5rem;
            margin-bottom: 0.25rem;
        }
    </style>
</body>
</html>