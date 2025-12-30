<?php
session_start();
require_once '../../../config/config.php';
require_once '../../../config/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/sidebar.php';

if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    header('Location: ../../../login.php');
    exit;
}

global $database;

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = $_POST['section'] ?? '';
    
    try {
        switch ($section) {
            case 'informations':
                // Mise à jour des informations personnelles
                $database->query("UPDATE utilisateurs SET 
                    prenom = ?, nom = ?, email = ?, telephone = ?
                    WHERE id = ?", [
                    $_POST['prenom'], $_POST['nom'], $_POST['email'], $_POST['telephone'], $_SESSION['user_id']
                ]);
                
                // Mise à jour du CV
                $database->query("UPDATE cvs SET 
                    titre_poste_recherche = ?, niveau_experience = ?, type_contrat = ?, 
                    salaire_souhaite = ?
                    WHERE utilisateur_id = ?", [
                    $_POST['titre_poste_recherche'], $_POST['niveau_experience'], 
                    $_POST['type_contrat'], $_POST['salaire_souhaite'], $_SESSION['user_id']
                ]);
                break;
                
            case 'competences':
                // Debug: Vérifier si un fichier est envoyé
                error_log("Section competences - Fichier reçu: " . (isset($_FILES['cv_file']) ? 'OUI' : 'NON'));
                if (isset($_FILES['cv_file'])) {
                    error_log("Erreur fichier: " . $_FILES['cv_file']['error']);
                }
                
                // Gestion du fichier CV
                $cv_file = null;
                if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === 0) {
                    // Récupérer l'ancien fichier CV pour le supprimer
                    $old_cv = $database->fetch("SELECT cv_file FROM cvs WHERE utilisateur_id = ?", [$_SESSION['user_id']]);
                    
                    $filename = 'cv_' . $_SESSION['user_id'] . '_' . time() . '.pdf';
                    $upload_path = '../../../uploads/cv/' . $filename;
                    
                    if (!is_dir('../../../uploads/cv/')) {
                        mkdir('../../../uploads/cv/', 0755, true);
                    }
                    
                    if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $upload_path)) {
                        $cv_file = $filename;
                        error_log("Nouveau CV uploadé: " . $filename);
                        
                        // Supprimer l'ancien fichier CV s'il existe
                        if ($old_cv && !empty($old_cv['cv_file'])) {
                            $old_file_path = '../../../uploads/cv/' . $old_cv['cv_file'];
                            if (file_exists($old_file_path)) {
                                unlink($old_file_path);
                                error_log("Ancien CV supprimé: " . $old_cv['cv_file']);
                            }
                        }
                    } else {
                        error_log("Erreur move_uploaded_file");
                    }
                }
                
                $update_query = "UPDATE cvs SET competences = ?, formations = ?, experiences_professionnelles = ?";
                $params = [$_POST['competences'], $_POST['formations'], $_POST['experiences_professionnelles']];
                
                if ($cv_file) {
                    $update_query .= ", cv_file = ?";
                    $params[] = $cv_file;
                    error_log("Mise à jour BDD avec nouveau CV: " . $cv_file);
                }
                
                // Ajouter les liens professionnels
                $update_query .= ", linkedin = ?, github = ?, portfolio = ?, site_web = ?";
                $params = array_merge($params, [
                    $_POST['linkedin'] ?? '',
                    $_POST['github'] ?? '',
                    $_POST['portfolio'] ?? '',
                    $_POST['site_web'] ?? ''
                ]);
                
                $update_query .= " WHERE utilisateur_id = ?";
                $params[] = $_SESSION['user_id'];
                
                error_log("Requête SQL: " . $update_query);
                $database->query($update_query, $params);
                break;
                
            case 'photo':
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['photo']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    
                    if (in_array($ext, $allowed)) {
                        $newname = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
                        $upload_path = '../../../uploads/profiles/' . $newname;
                        
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
                            $database->query("UPDATE utilisateurs SET photo_profil = ? WHERE id = ?", 
                                [$newname, $_SESSION['user_id']]);
                        }
                    }
                }
                break;
        }
        
        // Vérifier si profil complet
        $cv = $database->fetch("SELECT * FROM cvs WHERE utilisateur_id = ?", [$_SESSION['user_id']]);
        $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
        
        $completion = 0;
        if (!empty($cv['titre_poste_recherche'])) $completion += 20;
        if (!empty($cv['competences'])) $completion += 20;
        if (!empty($cv['formations'])) $completion += 20;
        if (!empty($cv['experiences_professionnelles'])) $completion += 20;
        if (!empty($user['photo_profil'])) $completion += 20;
        
        if ($completion >= 80) {
            $database->query("UPDATE utilisateurs SET statut = 'actif', profil_complet = 1 WHERE id = ?", [$_SESSION['user_id']]);
            flashMessage('Profil complété avec succès ! Votre compte est maintenant actif.', 'success');
        } else {
            flashMessage('Section mise à jour avec succès !', 'success');
        }
        
    } catch (Exception $e) {
        flashMessage('Erreur : ' . $e->getMessage(), 'error');
    }
    
    // Redirection PRG pour éviter la re-soumission
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Récupérer les données
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
$profile = $database->fetch("
    SELECT cv.*, u.nom, u.prenom, u.email, u.photo_profil, u.telephone, u.statut
    FROM cvs cv
    JOIN utilisateurs u ON cv.utilisateur_id = u.id
    WHERE cv.utilisateur_id = ?
", [$_SESSION['user_id']]);

$activeTab = $_GET['tab'] ?? 'informations';

// Calculer le pourcentage de completion
$progress = 0;
if (!empty($profile['titre_poste_recherche'])) $progress += 20;
if (!empty($profile['competences'])) $progress += 20;
if (!empty($profile['formations'])) $progress += 20;
if (!empty($profile['experiences_professionnelles'])) $progress += 20;
if (!empty($profile['photo_profil'])) $progress += 20;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuration du profil - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-container {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .profile-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .profile-header {
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            color: white;
            padding: 2rem;
        }
        .progress-ring {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: conic-gradient(#fff <?= $progress * 3.6 ?>deg, rgba(255,255,255,0.3) 0deg);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .progress-ring::before {
            content: '';
            width: 60px;
            height: 60px;
            background: #0099FF;
            border-radius: 50%;
            position: absolute;
        }
        .progress-text {
            position: relative;
            z-index: 1;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .nav-pills .nav-link {
            border-radius: 25px;
            padding: 0.75rem 1.5rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
        }
        .form-section {
            padding: 2rem;
        }
        .form-floating label {
            color: #6c757d;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 153, 255, 0.3);
            color: white;
        }
        .skill-tag {
            background: #e3f2fd;
            color: #0099ff;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.85rem;
            margin: 0.25rem;
            display: inline-block;
        }
        .photo-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        .photo-upload-area:hover {
            border-color: #0099ff;
            background: #f8f9ff;
        }
    </style>
</head>
<body class="profile-container">
    <?php renderSidebar($_SESSION['user_type'], 'profile/edit.php', $user); ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <?php if ($flashMessage = getFlashMessage()): ?>
                <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flashMessage['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- En-tête du profil -->
            <div class="profile-card mb-4">
                <div class="profile-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2">🎯 Configuration du profil</h1>
                            <p class="mb-0 opacity-75">Complétez votre profil pour maximiser vos opportunités</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="me-3">
                                    <div class="h5 mb-0"><?= $progress ?>% complété</div>
                                    <small class="opacity-75">Profil professionnel</small>
                                </div>
                                <div class="progress-ring">
                                    <div class="progress-text"><?= $progress ?>%</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenu principal -->
            <div class="row">
                <!-- Navigation des onglets -->
                <div class="col-md-3">
                    <div class="profile-card">
                        <div class="p-3">
                            <h6 class="text-muted mb-3">SECTIONS</h6>
                            <div class="nav flex-column nav-pills">
                                <a class="nav-link <?= $activeTab === 'informations' ? 'active' : '' ?>" href="?tab=informations">
                                    <i class="bi bi-person me-2"></i>Informations personnelles
                                </a>
                                <a class="nav-link <?= $activeTab === 'competences' ? 'active' : '' ?>" href="?tab=competences">
                                    <i class="bi bi-award me-2"></i>Compétences & Expérience
                                </a>
                                <a class="nav-link <?= $activeTab === 'photo' ? 'active' : '' ?>" href="?tab=photo">
                                    <i class="bi bi-camera me-2"></i>Photo de profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contenu des onglets -->
                <div class="col-md-9">
                    <div class="profile-card">
                        <?php if ($activeTab === 'informations'): ?>
                            <div class="form-section">
                                <h5 class="mb-4">📋 Informations personnelles</h5>
                                <form method="POST">
                                    <input type="hidden" name="section" value="informations">
                                    
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="prenom" name="prenom" 
                                                       value="<?= htmlspecialchars($profile['prenom'] ?? '') ?>" required>
                                                <label for="prenom">Prénom *</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="nom" name="nom" 
                                                       value="<?= htmlspecialchars($profile['nom'] ?? '') ?>" required>
                                                <label for="nom">Nom *</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
                                                <label for="email">Email *</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                                       value="<?= htmlspecialchars($profile['telephone'] ?? '') ?>">
                                                <label for="telephone">Téléphone</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-3">🎯 Recherche d'emploi</h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" id="titre_poste" name="titre_poste_recherche" 
                                                       value="<?= htmlspecialchars($profile['titre_poste_recherche'] ?? '') ?>" required>
                                                <label for="titre_poste">Poste recherché *</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="niveau_experience" name="niveau_experience">
                                                    <option value="debutant" <?= ($profile['niveau_experience'] ?? '') === 'debutant' ? 'selected' : '' ?>>Débutant (0-2 ans)</option>
                                                    <option value="junior" <?= ($profile['niveau_experience'] ?? '') === 'junior' ? 'selected' : '' ?>>Junior (2-5 ans)</option>
                                                    <option value="confirme" <?= ($profile['niveau_experience'] ?? '') === 'confirme' ? 'selected' : '' ?>>Confirmé (5-10 ans)</option>
                                                    <option value="senior" <?= ($profile['niveau_experience'] ?? '') === 'senior' ? 'selected' : '' ?>>Senior (+10 ans)</option>
                                                </select>
                                                <label for="niveau_experience">Niveau d'expérience</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select" id="type_contrat" name="type_contrat">
                                                    <option value="cdi" <?= ($profile['type_contrat'] ?? '') === 'cdi' ? 'selected' : '' ?>>CDI</option>
                                                    <option value="cdd" <?= ($profile['type_contrat'] ?? '') === 'cdd' ? 'selected' : '' ?>>CDD</option>
                                                    <option value="freelance" <?= ($profile['type_contrat'] ?? '') === 'freelance' ? 'selected' : '' ?>>Freelance</option>
                                                    <option value="stage" <?= ($profile['type_contrat'] ?? '') === 'stage' ? 'selected' : '' ?>>Stage</option>
                                                    <option value="interim" <?= ($profile['type_contrat'] ?? '') === 'interim' ? 'selected' : '' ?>>Intérim</option>
                                                </select>
                                                <label for="type_contrat">Type de contrat</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="salaire" name="salaire_souhaite" 
                                                       value="<?= $profile['salaire_souhaite'] ?? '' ?>">
                                                <label for="salaire">Salaire souhaité (€/an)</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end mt-4">
                                        <button type="submit" class="btn btn-gradient">
                                            <i class="bi bi-check-lg me-2"></i>Enregistrer les informations
                                        </button>
                                    </div>
                                </form>
                            </div>

                        <?php elseif ($activeTab === 'competences'): ?>
                            <div class="form-section">
                                <h5 class="mb-4">🏆 Compétences & Expérience</h5>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="section" value="competences">
                                    
                                    <!-- Compétences -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-primary mb-3">💻 Compétences techniques</h6>
                                        <div id="competences-container">
                                            <div class="input-group mb-2">
                                                <input type="text" class="form-control" placeholder="Ex: PHP, JavaScript, Python..." id="competence-input">
                                                <button type="button" class="btn btn-outline-primary" onclick="addCompetence()">Ajouter</button>
                                            </div>
                                            <div id="competences-list" class="d-flex flex-wrap gap-2 mb-3"></div>
                                            <textarea name="competences" id="competences-hidden" style="display:none;"><?= htmlspecialchars($profile['competences'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Formations -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-primary mb-3">🎓 Formations & Diplômes</h6>
                                        <div id="formations-container">
                                            <div class="row g-2 mb-2">
                                                <div class="col-md-4">
                                                    <input type="text" class="form-control" placeholder="Diplôme" id="formation-diplome">
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="text" class="form-control" placeholder="École/Université" id="formation-ecole">
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" placeholder="Année" id="formation-annee">
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-outline-primary" onclick="addFormation()">+</button>
                                                </div>
                                            </div>
                                            <div id="formations-list"></div>
                                            <textarea name="formations" id="formations-hidden" style="display:none;"><?= htmlspecialchars($profile['formations'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- Expériences -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-primary mb-3">💼 Expériences professionnelles</h6>
                                        <div id="experiences-container">
                                            <div class="card mb-2">
                                                <div class="card-body">
                                                    <div class="row g-2">
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control" placeholder="Poste" id="exp-poste">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control" placeholder="Entreprise" id="exp-entreprise">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control" placeholder="Période (ex: 2020-2023)" id="exp-periode">
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text" class="form-control" placeholder="Lieu" id="exp-lieu">
                                                        </div>
                                                        <div class="col-12">
                                                            <textarea class="form-control" rows="2" placeholder="Description des missions..." id="exp-description"></textarea>
                                                        </div>
                                                        <div class="col-12">
                                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addExperience()">Ajouter cette expérience</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="experiences-list"></div>
                                            <textarea name="experiences_professionnelles" id="experiences-hidden" style="display:none;"><?= htmlspecialchars($profile['experiences_professionnelles'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                    
                                    <!-- CV Upload -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-primary mb-3">📄 CV (PDF)</h6>
                                        <div class="border rounded p-3">
                                            <input type="file" class="form-control" name="cv_file" accept=".pdf" id="cv-upload">
                                            <small class="text-muted">Format PDF uniquement, max 5MB</small>
                                            <?php if (!empty($profile['cv_file'])): ?>
                                                <div class="mt-2">
                                                    <a href="../../../uploads/cv/<?= $profile['cv_file'] ?>" target="_blank" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-file-pdf"></i> Voir le CV actuel
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Liens professionnels -->
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-primary mb-3">🔗 Liens professionnels</h6>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">LinkedIn</label>
                                                <input type="url" class="form-control" name="linkedin" placeholder="https://linkedin.com/in/..." value="<?= htmlspecialchars($profile['linkedin'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">GitHub</label>
                                                <input type="url" class="form-control" name="github" placeholder="https://github.com/..." value="<?= htmlspecialchars($profile['github'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Portfolio</label>
                                                <input type="url" class="form-control" name="portfolio" placeholder="https://monportfolio.com" value="<?= htmlspecialchars($profile['portfolio'] ?? '') ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Site web</label>
                                                <input type="url" class="form-control" name="site_web" placeholder="https://monsite.com" value="<?= htmlspecialchars($profile['site_web'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-gradient">
                                            <i class="bi bi-award me-2"></i>Enregistrer le profil professionnel
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <script>
                            let competences = [];
                            let formations = [];
                            let experiences = [];
                            
                            // Charger les données existantes
                            document.addEventListener('DOMContentLoaded', function() {
                                loadExistingData();
                            });
                            
                            function loadExistingData() {
                                // Charger compétences
                                const competencesText = document.getElementById('competences-hidden').value;
                                if (competencesText) {
                                    competences = competencesText.split(',').map(c => c.trim()).filter(c => c);
                                    updateCompetencesList();
                                }
                                
                                // Charger formations
                                const formationsText = document.getElementById('formations-hidden').value;
                                if (formationsText) {
                                    formations = formationsText.split('\n').filter(f => f.trim());
                                    updateFormationsList();
                                }
                                
                                // Charger expériences
                                const experiencesText = document.getElementById('experiences-hidden').value;
                                if (experiencesText) {
                                    const expBlocks = experiencesText.split('---').filter(e => e.trim());
                                    experiences = expBlocks.map(block => {
                                        const lines = block.trim().split('\n');
                                        return {
                                            poste: lines[0] || '',
                                            entreprise: lines[1] || '',
                                            periode: lines[2] || '',
                                            lieu: lines[3] || '',
                                            description: lines.slice(4).join('\n') || ''
                                        };
                                    });
                                    updateExperiencesList();
                                }
                            }
                            
                            function addCompetence() {
                                const input = document.getElementById('competence-input');
                                const value = input.value.trim();
                                if (value && !competences.includes(value)) {
                                    competences.push(value);
                                    input.value = '';
                                    updateCompetencesList();
                                }
                            }
                            
                            function removeCompetence(index) {
                                competences.splice(index, 1);
                                updateCompetencesList();
                            }
                            
                            function updateCompetencesList() {
                                const container = document.getElementById('competences-list');
                                container.innerHTML = competences.map((comp, index) => 
                                    `<span class="badge bg-primary me-1 mb-1">${comp} <button type="button" class="btn-close btn-close-white ms-1" onclick="removeCompetence(${index})"></button></span>`
                                ).join('');
                                document.getElementById('competences-hidden').value = competences.join(', ');
                            }
                            
                            function addFormation() {
                                const diplome = document.getElementById('formation-diplome').value.trim();
                                const ecole = document.getElementById('formation-ecole').value.trim();
                                const annee = document.getElementById('formation-annee').value.trim();
                                
                                if (diplome && ecole) {
                                    formations.push(`${diplome} - ${ecole} (${annee})`);
                                    document.getElementById('formation-diplome').value = '';
                                    document.getElementById('formation-ecole').value = '';
                                    document.getElementById('formation-annee').value = '';
                                    updateFormationsList();
                                }
                            }
                            
                            function removeFormation(index) {
                                formations.splice(index, 1);
                                updateFormationsList();
                            }
                            
                            function updateFormationsList() {
                                const container = document.getElementById('formations-list');
                                container.innerHTML = formations.map((form, index) => 
                                    `<div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-1">
                                        <span>${form}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFormation(${index})">×</button>
                                    </div>`
                                ).join('');
                                document.getElementById('formations-hidden').value = formations.join('\n');
                            }
                            
                            function addExperience() {
                                const poste = document.getElementById('exp-poste').value.trim();
                                const entreprise = document.getElementById('exp-entreprise').value.trim();
                                const periode = document.getElementById('exp-periode').value.trim();
                                const lieu = document.getElementById('exp-lieu').value.trim();
                                const description = document.getElementById('exp-description').value.trim();
                                
                                if (poste && entreprise) {
                                    experiences.push({poste, entreprise, periode, lieu, description});
                                    document.getElementById('exp-poste').value = '';
                                    document.getElementById('exp-entreprise').value = '';
                                    document.getElementById('exp-periode').value = '';
                                    document.getElementById('exp-lieu').value = '';
                                    document.getElementById('exp-description').value = '';
                                    updateExperiencesList();
                                }
                            }
                            
                            function removeExperience(index) {
                                experiences.splice(index, 1);
                                updateExperiencesList();
                            }
                            
                            function updateExperiencesList() {
                                const container = document.getElementById('experiences-list');
                                container.innerHTML = experiences.map((exp, index) => 
                                    `<div class="card mb-2">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="mb-1">${exp.poste} - ${exp.entreprise}</h6>
                                                    <small class="text-muted">${exp.periode} • ${exp.lieu}</small>
                                                    <p class="mb-0 mt-1">${exp.description}</p>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeExperience(${index})">×</button>
                                            </div>
                                        </div>
                                    </div>`
                                ).join('');
                                
                                const expText = experiences.map(exp => 
                                    `${exp.poste}\n${exp.entreprise}\n${exp.periode}\n${exp.lieu}\n${exp.description}`
                                ).join('\n---\n');
                                document.getElementById('experiences-hidden').value = expText;
                            }
                            
                            // Permettre l'ajout avec Entrée
                            document.getElementById('competence-input').addEventListener('keypress', function(e) {
                                if (e.key === 'Enter') {
                                    e.preventDefault();
                                    addCompetence();
                                }
                            });
                            </script>

                        <?php elseif ($activeTab === 'photo'): ?>
                            <div class="form-section">
                                <h5 class="mb-4">📸 Photo de profil</h5>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="text-center mb-4">
                                            <?php if ($profile['photo_profil']): ?>
                                                <img src="../../../uploads/profiles/<?= $profile['photo_profil'] ?>" 
                                                     class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                                                <p class="text-success"><i class="bi bi-check-circle"></i> Photo actuelle</p>
                                            <?php else: ?>
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white mx-auto mb-3" 
                                                     style="width: 150px; height: 150px; font-size: 3rem;">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <p class="text-muted">Aucune photo</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <form method="POST" enctype="multipart/form-data">
                                            <input type="hidden" name="section" value="photo">
                                            
                                            <div class="photo-upload-area mb-3">
                                                <i class="bi bi-cloud-upload" style="font-size: 3rem; color: #0099ff;"></i>
                                                <h6 class="mt-2">Choisir une nouvelle photo</h6>
                                                <p class="text-muted mb-3">JPG, PNG ou GIF - Max 5MB</p>
                                                <input type="file" class="form-control" name="photo" accept="image/*" required>
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i>
                                                <strong>Conseils :</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li>Utilisez une photo récente et professionnelle</li>
                                                    <li>Regardez l'objectif avec un sourire naturel</li>
                                                    <li>Évitez les photos de groupe ou floues</li>
                                                </ul>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-gradient w-100">
                                                <i class="bi bi-upload me-2"></i>Télécharger la photo
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animation du cercle de progression
        document.addEventListener('DOMContentLoaded', function() {
            const progressRing = document.querySelector('.progress-ring');
            const progress = <?= $progress ?>;
            
            // Animation CSS pour le cercle
            progressRing.style.background = `conic-gradient(#fff ${progress * 3.6}deg, rgba(255,255,255,0.3) 0deg)`;
        });
    </script>
</body>
</html>