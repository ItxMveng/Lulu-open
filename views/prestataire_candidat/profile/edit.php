<?php
require_once '../../../config/config.php';
requireLogin();

if ($_SESSION['user_type'] !== 'prestataire_candidat') {
    redirect('../../../index.php');
}

global $database;
$userId = $_SESSION['user_id'];

// Récupérer les données utilisateur
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$userId]);

// Récupérer les profils
$prestataire = $database->fetch("SELECT * FROM profils_prestataires WHERE utilisateur_id = ?", [$userId]);
$candidat = $database->fetch("SELECT * FROM cvs WHERE utilisateur_id = ?", [$userId]);
$categories = $database->fetchAll("SELECT * FROM categories_services WHERE actif = 1 ORDER BY nom");

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

// Traitement du formulaire prestataire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_type']) && $_POST['profile_type'] === 'prestataire') {
    try {
        $profileData = [
            'titre_professionnel' => trim($_POST['titre_professionnel'] ?? ''),
            'description_services' => trim($_POST['description_services'] ?? ''),
            'tarif_horaire' => $_POST['tarif_horaire'] ?? null,
            'disponibilite' => $_POST['disponibilite'] ?? 1,
            'categorie_id' => $_POST['categorie_id'] ?? null
        ];

        if ($prestataire) {
            $database->update('profils_prestataires', $profileData, 'utilisateur_id = ?', [$userId]);
        } else {
            $profileData['utilisateur_id'] = $userId;
            $database->insert('profils_prestataires', $profileData);
        }

        // Vérifier si le profil est maintenant complet
        $isProfileComplete = checkProfileCompletion($userId, $_SESSION['user_type']);

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

        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Profil prestataire mis à jour avec succès'];

    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => $e->getMessage()];
    }
}

// Traitement du formulaire candidat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_type']) && $_POST['profile_type'] === 'candidat') {
    try {
        $cvData = [
            'titre_poste_recherche' => trim($_POST['titre_poste_recherche'] ?? ''),
            'competences' => trim($_POST['competences'] ?? ''),
            'type_contrat' => $_POST['type_contrat'] ?? '',
            'salaire_souhaite' => $_POST['salaire_souhaite'] ?? null,
            'categorie_id' => $_POST['categorie_id'] ?? null
        ];

        if ($candidat) {
            $database->update('cvs', $cvData, 'utilisateur_id = ?', [$userId]);
        } else {
            $cvData['utilisateur_id'] = $userId;
            $database->insert('cvs', $cvData);
        }

        // Vérifier si le profil est maintenant complet
        $isProfileComplete = checkProfileCompletion($userId, $_SESSION['user_type']);

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
                'contenu' => "Bravo {$user['prenom']} !\n\nVotre profil est maintenant complet et votre compte est actif sur LULU-OPEN. Vous êtes désormais visible par les recruteurs potentiels.\n\nContinuez à optimiser votre CV en ajoutant des expériences et formations pour maximiser vos chances.\n\nL'équipe LULU-OPEN",
                'lu' => 0,
                'date_envoi' => date('Y-m-d H:i:s')
            ]);
        }

        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Profil candidat mis à jour avec succès'];

    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="bi bi-person-gear"></i> Modifier mon profil</h1>
                    <a href="../dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Retour
                    </a>
                </div>

                <?php if ($flashMessage = getFlashMessage()): ?>
                    <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                        <?= htmlspecialchars($flashMessage['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Tabs -->
                <ul class="nav nav-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#prestataire">
                            <i class="bi bi-briefcase"></i> Profil Prestataire
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#candidat">
                            <i class="bi bi-file-person"></i> Profil Candidat
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Profil Prestataire -->
                    <div class="tab-pane fade show active" id="prestataire">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="../../../api/update-profile.php">
                                    <input type="hidden" name="profile_type" value="prestataire">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Titre professionnel *</label>
                                        <input type="text" class="form-control" name="titre_professionnel" 
                                               value="<?= htmlspecialchars($prestataire['titre_professionnel'] ?? '') ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Description de vos services *</label>
                                        <textarea class="form-control" name="description_services" rows="5" required><?= htmlspecialchars($prestataire['description_services'] ?? '') ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Tarif horaire (€)</label>
                                            <input type="number" class="form-control" name="tarif_horaire" step="0.01"
                                                   value="<?= $prestataire['tarif_horaire'] ?? '' ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Disponibilité</label>
                                            <select class="form-select" name="disponibilite">
                                                <option value="1" <?= ($prestataire['disponibilite'] ?? 1) == 1 ? 'selected' : '' ?>>Disponible</option>
                                                <option value="0" <?= ($prestataire['disponibilite'] ?? 1) == 0 ? 'selected' : '' ?>>Non disponible</option>
                                            </select>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Enregistrer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Profil Candidat -->
                    <div class="tab-pane fade" id="candidat">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" action="../../../api/update-profile.php">
                                    <input type="hidden" name="profile_type" value="candidat">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Poste recherché *</label>
                                        <input type="text" class="form-control" name="titre_poste_recherche" 
                                               value="<?= htmlspecialchars($candidat['titre_poste_recherche'] ?? '') ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Compétences *</label>
                                        <textarea class="form-control" name="competences" rows="4" required><?= htmlspecialchars($candidat['competences'] ?? '') ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Type de contrat</label>
                                            <select class="form-select" name="type_contrat">
                                                <option value="cdi" <?= ($candidat['type_contrat'] ?? '') == 'cdi' ? 'selected' : '' ?>>CDI</option>
                                                <option value="cdd" <?= ($candidat['type_contrat'] ?? '') == 'cdd' ? 'selected' : '' ?>>CDD</option>
                                                <option value="freelance" <?= ($candidat['type_contrat'] ?? '') == 'freelance' ? 'selected' : '' ?>>Freelance</option>
                                                <option value="stage" <?= ($candidat['type_contrat'] ?? '') == 'stage' ? 'selected' : '' ?>>Stage</option>
                                                <option value="alternance" <?= ($candidat['type_contrat'] ?? '') == 'alternance' ? 'selected' : '' ?>>Alternance</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Salaire souhaité (€/an)</label>
                                            <input type="number" class="form-control" name="salaire_souhaite"
                                                   value="<?= $candidat['salaire_souhaite'] ?? '' ?>">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Enregistrer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
