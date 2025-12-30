<?php
session_start();
require_once '../../config/config.php';
require_once '../../config/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/sidebar.php';

if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    header('Location: ../../login.php');
    exit;
}

global $database;

$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);

$profile = $database->fetch("
    SELECT cv.*, u.nom, u.prenom, u.email, u.photo_profil, u.telephone, 
           l.ville, l.pays, cs.nom as categorie_nom
    FROM cvs cv
    JOIN utilisateurs u ON cv.utilisateur_id = u.id
    LEFT JOIN localisations l ON u.localisation_id = l.id
    LEFT JOIN categories_services cs ON cv.categorie_id = cs.id
    WHERE cv.utilisateur_id = ?
", [$_SESSION['user_id']]);

if (!$profile) {
    $database->insert('cvs', [
        'utilisateur_id' => $_SESSION['user_id'],
        'categorie_id' => 1,
        'titre_poste_recherche' => '',
        'niveau_experience' => 'debutant',
        'type_contrat' => 'cdi',
        'competences' => ''
    ]);
    
    $profile = $database->fetch("
        SELECT cv.*, u.nom, u.prenom, u.email, u.photo_profil, u.telephone, 
               l.ville, l.pays, cs.nom as categorie_nom
        FROM cvs cv
        JOIN utilisateurs u ON cv.utilisateur_id = u.id
        LEFT JOIN localisations l ON u.localisation_id = l.id
        LEFT JOIN categories_services cs ON cv.categorie_id = cs.id
        WHERE cv.utilisateur_id = ?
    ", [$_SESSION['user_id']]);
}

$profilComplet = 1;
if (empty($profile['titre_poste_recherche']) || empty($profile['competences']) || 
    empty($profile['formations']) || empty($profile['experiences_professionnelles'])) {
    $profilComplet = 0;
}

if ($user['statut'] === 'en_attente' && $profilComplet) {
    $database->query("UPDATE utilisateurs SET statut = 'actif' WHERE id = ?", [$_SESSION['user_id']]);
    $user['statut'] = 'actif';
}

$stats = [
    'candidatures' => 0,
    'entretiens' => 0,
    'messages' => $database->fetch("SELECT COUNT(*) as count FROM messages WHERE destinataire_id = ? AND lu = 0", [$_SESSION['user_id']])['count'] ?? 0,
    'profile_views' => 0
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Candidat - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
</head>
<body>
    <?php renderSidebar($_SESSION['user_type'], 'dashboard.php', $user); ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <?php if ($flashMessage = getFlashMessage()): ?>
                <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flashMessage['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!$profilComplet): ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <div class="d-flex align-items-center">
                        <div class="me-3">⚠️</div>
                        <div class="flex-grow-1">
                            <h6 class="alert-heading mb-1">Profil incomplet</h6>
                            <p class="mb-2">Votre profil n'est pas encore complet. Complétez-le pour être actif et visible sur la plateforme.</p>
                            <a href="profile/edit.php" class="btn btn-warning btn-sm">Compléter mon profil</a>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="mb-4">
                <h1 class="h3 mb-0 text-primary">Bonjour <?= htmlspecialchars($profile['prenom'] ?? 'Candidat') ?> ! 👋</h1>
                <p class="text-muted mb-0">Gérez votre CV et trouvez votre prochain emploi</p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-0"><?= $stats['candidatures'] ?></h3>
                                    <p class="mb-0">Candidatures</p>
                                </div>
                                <div class="ms-3"><i class="bi-briefcase" style="font-size: 2rem;"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-0"><?= $stats['entretiens'] ?></h3>
                                    <p class="mb-0">Entretiens</p>
                                </div>
                                <div class="ms-3"><i class="bi-calendar-check" style="font-size: 2rem;"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-0"><?= $stats['messages'] ?></h3>
                                    <p class="mb-0">Messages</p>
                                </div>
                                <div class="ms-3"><i class="bi-envelope" style="font-size: 2rem;"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <h3 class="mb-0"><?= $stats['profile_views'] ?></h3>
                                    <p class="mb-0">Vues profil</p>
                                </div>
                                <div class="ms-3"><i class="bi-eye" style="font-size: 2rem;"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Mon Profil Candidat</h5>
                    <a href="profile/edit.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Modifier
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="mb-3">
                                <?php if ($profile['photo_profil']): ?>
                                    <img src="../../uploads/profiles/<?= $profile['photo_profil'] ?>" 
                                         alt="Photo de profil" class="rounded-circle" width="80" height="80">
                                <?php else: ?>
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white mx-auto" 
                                         style="width: 80px; height: 80px; font-size: 1.5rem;">
                                        <?= strtoupper(substr($profile['prenom'] ?? 'C', 0, 1) . substr($profile['nom'] ?? 'A', 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h6><?= htmlspecialchars(($profile['prenom'] ?? '') . ' ' . ($profile['nom'] ?? '')) ?></h6>
                            <p class="text-muted small"><?= htmlspecialchars($profile['ville'] ?? 'Non renseigné') ?></p>
                        </div>
                        <div class="col-md-9">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Poste recherché</h6>
                                    <p class="mb-0"><?= htmlspecialchars($profile['titre_poste_recherche'] ?? 'Non renseigné') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Domaine</h6>
                                    <p class="mb-0"><?= htmlspecialchars($profile['categorie_nom'] ?? 'Non renseigné') ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Niveau d'expérience</h6>
                                    <span class="badge bg-primary">
                                        <?= ucfirst($profile['niveau_experience'] ?? 'Non renseigné') ?>
                                    </span>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Type de contrat</h6>
                                    <span class="badge bg-success">
                                        <?= strtoupper($profile['type_contrat'] ?? 'Non renseigné') ?>
                                    </span>
                                </div>
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