<?php
// Inclure les fichiers nécessaires
require_once 'config/config.php';
require_once 'includes/functions.php';

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer l'ID du profil
$id = intval($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(404);
    echo "<h1>Profil non trouvé</h1>";
    echo "<p>Identifiant de profil manquant.</p>";
    echo "<a href='/lulu/'>Retour à l'accueil</a>";
    exit;
}

// Debug temporaire - Afficher si on arrive ici
echo "<!-- Debug: Initialisation OK -->";

try {
    // Debug temporaire
    echo "<!-- Debug: Avant connexion DB -->";

    // Vérifier que $database existe
    if (!isset($database)) {
        throw new Exception('$database non défini');
    }
    echo "<!-- Debug: DB connectée -->";

    // Déterminer si l'utilisateur est prestataire ou candidat
   // Simpler approach: first get user, then check profiles separately
$userCheck = $database->fetch("SELECT type_utilisateur FROM utilisateurs WHERE id = ? AND statut = 'actif'", [$id]);

if (!$userCheck) {
    throw new Exception('Utilisateur non trouvé ou inactif');
}

// Check for profiles
$hasPrestataire = $database->fetchColumn("SELECT COUNT(*) FROM profils_prestataires WHERE utilisateur_id = ?", [$id]);
$hasCandidat = $database->fetchColumn("SELECT COUNT(*) FROM cvs WHERE utilisateur_id = ?", [$id]);

$profileType = null;

// Déterminer le type de profil réel (supports prestataire_candidat)
if (($userCheck['type_utilisateur'] === 'prestataire' || $userCheck['type_utilisateur'] === 'prestataire_candidat') && $hasPrestataire > 0) {
    $profileType = 'prestataire';
} elseif (($userCheck['type_utilisateur'] === 'candidat' || $userCheck['type_utilisateur'] === 'prestataire_candidat') && $hasCandidat > 0) {
    $profileType = 'candidat';
} else {
    // Aucun profil trouvé
    http_response_code(404);
    echo "<h1>Profil non trouvé</h1>";
    echo "<p>Aucun profil actif trouvé pour cet utilisateur.</p>";
    echo "<a href='/lulu/'>Retour à l'accueil</a>";
    exit;
}


    if ($profileType === 'prestataire') {
        // Récupérer le profil prestataire
        $profile = $database->fetch("
            SELECT u.*, p.*, c.nom as categorie_nom, c.icone as categorie_icone,
                   l.ville, l.pays, l.region,
                   0 as note_moyenne,
                   0 as nombre_avis,
                   'prestataire' as profile_type
            FROM utilisateurs u
            JOIN profils_prestataires p ON u.id = p.utilisateur_id
            LEFT JOIN categories_services c ON p.categorie_id = c.id
            LEFT JOIN localisations l ON u.localisation_id = l.id
            WHERE u.id = ? AND u.statut = 'actif'
        ", [$id]);

        if (!$profile) {
            http_response_code(404);
            echo "<h1>Profil non trouvé</h1>";
            echo "<p>Impossible de charger les données du profil prestataire.</p>";
            echo "<a href='/lulu/'>Retour à l'accueil</a>";
            exit;
        }

        // Récupérer les avis (table non existante pour le moment)
        $reviews = [];

        // Récupérer le portfolio
        $portfolio = $database->fetchAll("
            SELECT * FROM portfolios
            WHERE prestataire_id = ?
            ORDER BY created_at DESC
        ", [$profile['id']]);

        // Vérifier abonnement actif pour prestataires (table non existante pour le moment)
        $abonnement_actif = false;

        $profile['abonnement_actif'] = $abonnement_actif;

    } elseif ($profileType === 'candidat') {
        // Récupérer le profil candidat avec catégories multiples
        $profile = $database->fetch("
            SELECT u.*, cv.*,
                   l.ville, l.pays, l.region,
                   0 as note_moyenne,
                   0 as nombre_avis,
                   'candidat' as profile_type
            FROM utilisateurs u
            JOIN cvs cv ON u.id = cv.utilisateur_id
            LEFT JOIN localisations l ON u.localisation_id = l.id
            WHERE u.id = ? AND u.statut = 'actif'
        ", [$id]);

        if (!$profile) {
            http_response_code(404);
            echo "<h1>Profil non trouvé</h1>";
            echo "<p>Impossible de charger les données du profil candidat.</p>";
            echo "<a href='/lulu/'>Retour à l'accueil</a>";
            exit;
        }

        // Récupérer les catégories du candidat
        $categories = $database->fetchAll("
            SELECT c.id, c.nom, c.icone
            FROM categories_services c
            JOIN candidat_categories cc ON c.id = cc.categorie_id
            WHERE cc.candidat_id = ?
        ", [$id]);
        
        // Si pas de catégories dans la table de liaison, essayer avec categorie_id du CV
        if (empty($categories) && !empty($profile['categorie_id'])) {
            $categories = $database->fetchAll("
                SELECT id, nom, icone FROM categories_services WHERE id = ?
            ", [$profile['categorie_id']]);
        }
        
        $profile['categories'] = $categories;

        // Récupérer les avis (table non existante pour le moment)
        $reviews = [];

        // Vérifier abonnement actif pour candidats (table non existante pour le moment)
        $abonnement_actif = false;

        $profile['abonnement_actif'] = $abonnement_actif;
    }

} catch (Exception $e) {
    error_log("Erreur dans profile-detail.php: " . $e->getMessage());
    http_response_code(500);
    echo "<h1>Erreur serveur</h1>";
    echo "<p>Une erreur est survenue lors du chargement du profil.</p>";
    echo "<a href='/lulu/'>Retour à l'accueil</a>";
    exit;
}

// Fonctions helper pour la vue
if (!function_exists('formatPrice')) {
    function formatPrice($price) {
        return number_format($price, 2, ',', ' ') . ' €';
    }
}

if (!function_exists('formatDate')) {
    function formatDate($date) {
        return date('d/m/Y', strtotime($date));
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?= htmlspecialchars($profile['prenom'] . ' ' . $profile['nom']) ?> - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/animations.css" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="container-fluid py-4">
    <!-- Header du profil -->
    <div class="profile-header mb-5">
        <div class="row align-items-center">
            <div class="col-lg-3 text-center">
                <div class="profile-avatar-large mb-3">
                    <?php if ($profile['photo_profil']): ?>
                        <img src="uploads/profiles/<?= $profile['photo_profil'] ?>"
                             alt="Photo de <?= htmlspecialchars($profile['prenom']) ?>"
                             class="rounded-circle img-fluid">
                    <?php else: ?>
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white mx-auto">
                            <?= strtoupper(substr($profile['prenom'], 0, 1) . substr($profile['nom'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Badge de vérification -->
                <?php if ($profile['abonnement_actif']): ?>
                    <div class="verification-badge mb-3">
                        <i class="bi bi-patch-check-fill text-success"></i>
                        <span class="text-success fw-bold">Profil vérifié</span>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <?php if (isLoggedIn() && $_SESSION['user_id'] != $profile['id']): ?>
                    <div class="profile-actions d-grid gap-2">
                        <button class="btn btn-primary" onclick="contactUser(<?= $profile['id'] ?>)">
                            <i class="bi bi-chat-dots"></i> Contacter
                        </button>
                        <button class="btn btn-outline-secondary" onclick="toggleFavorite(<?= $profile['id'] ?>)" id="favoriteBtn">
                            <i class="bi bi-heart"></i> <span id="favoriteText">Ajouter aux favoris</span>
                        </button>
                        <button class="btn btn-outline-info" onclick="shareProfile()">
                            <i class="bi bi-share"></i> Partager
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-9">
                <div class="profile-info">
                    <h1 class="profile-name"><?= htmlspecialchars($profile['prenom'] . ' ' . $profile['nom']) ?></h1>

                    <?php if ($profileType === 'prestataire'): ?>
                        <h2 class="profile-title text-primary"><?= htmlspecialchars($profile['titre_professionnel']) ?></h2>
                    <?php else: ?>
                        <h2 class="profile-title text-info"><?= htmlspecialchars($profile['titre_poste_recherche']) ?></h2>
                    <?php endif; ?>

                    <div class="profile-meta mb-4">
                        <span class="meta-item">
                            <i class="bi bi-geo-alt text-muted"></i>
                            <?= htmlspecialchars(($profile['ville'] ?? 'Ville non spécifiée') . ', ' . (($profile['region'] ?? '') ?: ($profile['pays'] ?? 'Pays non spécifié'))) ?>
                        </span>

                        <span class="meta-item">
                            <i class="bi bi-tag text-muted"></i>
                            <?php if ($profileType === 'candidat' && !empty($profile['categories'])): ?>
                                <?php foreach ($profile['categories'] as $index => $cat): ?>
                                    <?= htmlspecialchars($cat['nom']) ?><?= $index < count($profile['categories']) - 1 ? ', ' : '' ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?= htmlspecialchars($profile['categorie_nom'] ?? 'Catégorie non spécifiée') ?>
                            <?php endif; ?>
                        </span>

                        <span class="meta-item">
                            <i class="bi bi-calendar text-muted"></i>
                            Membre depuis <?= date('M Y', strtotime($profile['date_inscription'])) ?>
                        </span>
                    </div>

                    <!-- Statistiques -->
                    <div class="profile-stats">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-star-fill text-warning"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value"><?= number_format($profile['note_moyenne'] ?? 0, 1) ?></div>
                                        <div class="stat-label">Note moyenne</div>
                                        <div class="stat-sublabel"><?= $profile['nombre_avis'] ?? 0 ?> avis</div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($profileType === 'prestataire'): ?>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-currency-euro text-success"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= formatPrice($profile['tarif_horaire']) ?></div>
                                            <div class="stat-label">Tarif/heure</div>
                                            <?php if ($profile['tarif_forfait']): ?>
                                                <div class="stat-sublabel">Forfait: <?= formatPrice($profile['tarif_forfait']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-briefcase text-primary"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= $profile['experience_annees'] ?? 0 ?></div>
                                            <div class="stat-label">Années d'expérience</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-check-circle text-<?= $profile['disponibilite'] === 'disponible' ? 'success' : 'warning' ?>"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= ucfirst($profile['disponibilite'] ?? 'Inconnu') ?></div>
                                            <div class="stat-label">Disponibilité</div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-trophy text-info"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= ucfirst($profile['niveau_experience']) ?></div>
                                            <div class="stat-label">Niveau</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-file-text text-secondary"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= strtoupper($profile['type_contrat']) ?></div>
                                            <div class="stat-label">Type de contrat</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-currency-euro text-success"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= $profile['salaire_souhaite'] ? formatPrice($profile['salaire_souhaite']) : 'Négociable' ?></div>
                                            <div class="stat-label">Salaire souhaité</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenu principal -->
    <div class="row g-4">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Description/Présentation -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-lines-fill"></i>
                        <?= $profileType === 'prestataire' ? 'Présentation des services' : 'Profil professionnel' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($profileType === 'prestataire'): ?>
                        <p class="lead"><?= nl2br(htmlspecialchars($profile['description_services'])) ?></p>

                        <?php if ($profile['diplomes']): ?>
                            <div class="mt-4">
                                <h6><i class="bi bi-mortarboard"></i> Diplômes et certifications</h6>
                                <p><?= nl2br(htmlspecialchars($profile['diplomes'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($profile['certifications']): ?>
                            <div class="mt-4">
                                <h6><i class="bi bi-award"></i> Certifications</h6>
                                <p><?= nl2br(htmlspecialchars($profile['certifications'])) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Affichage pour candidat -->
                        <?php if (!empty($profile['categories'])): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-tags"></i> Domaines de compétences</h6>
                                <div class="categories-list">
                                    <?php foreach ($profile['categories'] as $cat): ?>
                                        <span class="category-badge">
                                            <?php if ($cat['icone']): ?>
                                                <i class="<?= htmlspecialchars($cat['icone']) ?> me-2"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($cat['nom']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($profile['competences']): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-gear"></i> Compétences techniques</h6>
                                <div class="competences-cloud">
                                    <?php 
                                    $competences = is_string($profile['competences']) ? explode(',', $profile['competences']) : [];
                                    foreach ($competences as $competence): 
                                    ?>
                                        <span class="competence-tag"><?= htmlspecialchars(trim($competence)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($profile['formations']): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-mortarboard"></i> Formations</h6>
                                <div class="formation-content">
                                    <?php 
                                    $formations = explode("\n", $profile['formations']);
                                    foreach ($formations as $formation):
                                        if (trim($formation)):
                                    ?>
                                        <div class="formation-item">
                                            <i class="bi bi-mortarboard-fill text-primary me-2"></i>
                                            <?= htmlspecialchars(trim($formation)) ?>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($profile['experiences_professionnelles']): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-briefcase"></i> Expériences professionnelles</h6>
                                <div class="experience-content">
                                    <?php 
                                    // Parser les expériences structurées
                                    $exp_blocks = explode('---', $profile['experiences_professionnelles']);
                                    foreach ($exp_blocks as $block):
                                        $lines = array_filter(explode("\n", trim($block)));
                                        if (count($lines) >= 4):
                                    ?>
                                        <div class="experience-item">
                                            <h6 class="text-primary"><?= htmlspecialchars($lines[0]) ?></h6>
                                            <div class="text-muted mb-2">
                                                <strong><?= htmlspecialchars($lines[1]) ?></strong> • 
                                                <?= htmlspecialchars($lines[2]) ?> • 
                                                <?= htmlspecialchars($lines[3]) ?>
                                            </div>
                                            <?php if (isset($lines[4])): ?>
                                                <p class="mb-0"><?= nl2br(htmlspecialchars(implode("\n", array_slice($lines, 4)))) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Liens professionnels -->
                        <?php if ($profile['linkedin'] || $profile['github'] || $profile['portfolio'] || $profile['site_web']): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-link-45deg"></i> Liens professionnels</h6>
                                <div class="social-links">
                                    <?php if ($profile['linkedin']): ?>
                                        <a href="<?= htmlspecialchars($profile['linkedin']) ?>" target="_blank" class="social-link linkedin">
                                            <i class="bi bi-linkedin"></i> LinkedIn
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($profile['github']): ?>
                                        <a href="<?= htmlspecialchars($profile['github']) ?>" target="_blank" class="social-link github">
                                            <i class="bi bi-github"></i> GitHub
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($profile['portfolio']): ?>
                                        <a href="<?= htmlspecialchars($profile['portfolio']) ?>" target="_blank" class="social-link portfolio">
                                            <i class="bi bi-briefcase"></i> Portfolio
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($profile['site_web']): ?>
                                        <a href="<?= htmlspecialchars($profile['site_web']) ?>" target="_blank" class="social-link website">
                                            <i class="bi bi-globe"></i> Site web
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- CV téléchargeable -->
                        <?php if ($profile['cv_file'] && isLoggedIn()): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-file-earmark-pdf"></i> Curriculum Vitae</h6>
                                <div class="cv-download-section">
                                    <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded">
                                        <div>
                                            <i class="bi bi-file-earmark-pdf text-danger me-2" style="font-size: 1.5rem;"></i>
                                            <span class="fw-bold">CV de <?= htmlspecialchars($profile['prenom']) ?></span>
                                        </div>
                                        <div>
                                            <button class="btn btn-outline-primary btn-sm me-2" onclick="toggleCVPreview()" id="cvPreviewBtn">
                                                <i class="bi bi-eye"></i> Prévisualiser
                                            </button>
                                            <a href="uploads/cv/<?= $profile['cv_file'] ?>" class="btn btn-primary btn-sm" target="_blank">
                                                <i class="bi bi-download"></i> Télécharger
                                            </a>
                                        </div>
                                    </div>
                                    <div id="cvPreviewContainer" class="mt-3" style="display: none;">
                                        <iframe id="cvPreviewFrame" src="" width="100%" height="600px" style="border: 1px solid #dee2e6; border-radius: 8px;"></iframe>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Portfolio/Réalisations (pour prestataires) -->
            <?php if ($profileType === 'prestataire' && !empty($portfolio)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-images"></i> Portfolio
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="portfolio-grid">
                            <?php foreach ($portfolio as $item): ?>
                                <div class="portfolio-item" onclick="openPortfolioModal('<?= $item['image'] ?>', '<?= htmlspecialchars($item['description']) ?>')">
                                    <img src="uploads/portfolios/<?= $item['image'] ?>" alt="Réalisation" class="img-fluid">
                                    <div class="portfolio-overlay">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CV prévisualisable (pour candidats) -->
            <?php if (($profileType === 'candidat' || $profileType === 'prestataire_candidat') && isset($profile['cv_file']) && !empty($profile['cv_file'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-pdf"></i> Curriculum Vitae
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="cv-viewer">
                            <h6 class="mb-3">CV de <?= htmlspecialchars($profile['prenom']) ?></h6>

                            <!-- Boutons d'actions -->
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <button class="btn btn-outline-primary btn-sm" onclick="togglePreview()" id="previewBtn">
                                    <i class="bi bi-eye"></i> Prévisualiser
                                </button>
                                <a href="uploads/cv/<?= $profile['cv_file'] ?>" class="btn btn-primary btn-sm" download>
                                    <i class="bi bi-download"></i> Télécharger
                                </a>
                            </div>

                            <!-- Conteneur du visualiseur PDF -->
                            <div id="pdfViewer" class="pdf-viewer-container" style="display: none;">
                                <iframe src="uploads/cv/<?= $profile['cv_file'] ?>"
                                        class="pdf-iframe"
                                        width="100%"
                                        height="600px"
                                        style="border: 1px solid #dee2e6; border-radius: 8px;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Informations de contact -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle"></i> Informations
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <i class="bi bi-geo-alt text-muted"></i>
                        <span><?= htmlspecialchars(($profile['ville'] ?? 'Ville non spécifiée') . ', ' . (($profile['region'] ?? '') ?: ($profile['pays'] ?? 'Pays non spécifié'))) ?></span>
                    </div>

                    <?php if ($profileType === 'prestataire' && $profile['rayon_intervention']): ?>
                        <div class="info-item">
                            <i class="bi bi-compass text-muted"></i>
                            <span>Rayon d'intervention: <?= $profile['rayon_intervention'] ?> km</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($profileType === 'candidat' && $profile['mobilite']): ?>
                        <div class="info-item">
                            <i class="bi bi-airplane text-muted"></i>
                            <span>Ouvert à la mobilité</span>
                        </div>
                    <?php endif; ?>

                    <div class="info-item">
                        <i class="bi bi-calendar text-muted"></i>
                        <span>Membre depuis <?= date('F Y', strtotime($profile['date_inscription'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Avis récents -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-star"></i> Avis (<?= count($reviews) ?>)
                    </h6>
                    <?php if (isLoggedIn() && $_SESSION['user_id'] != $profile['id']): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="openReviewModal()">
                            <i class="bi bi-plus"></i> Avis
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                            <div class="review-item-compact">
                                <div class="d-flex align-items-start">
                                    <div class="review-avatar me-2">
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white"
                                             style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            <?= strtoupper(substr($review['donneur_nom'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="fw-bold"><?= htmlspecialchars($review['donneur_nom']) ?></small>
                                            <div class="rating-small">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?= $i <= $review['note'] ? '-fill text-warning' : ' text-muted' ?>" style="font-size: 0.7rem;"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php if ($review['commentaire']): ?>
                                            <p class="small text-muted mb-1"><?= htmlspecialchars(substr($review['commentaire'], 0, 80)) ?>...</p>
                                        <?php endif; ?>
                                        <small class="text-muted"><?= formatDate($review['date_avis']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (count($reviews) > 3): ?>
                            <div class="text-center mt-3">
                                <button class="btn btn-sm btn-outline-secondary" onclick="showAllReviews()">
                                    Voir tous les avis (<?= count($reviews) ?>)
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-star" style="font-size: 2rem;"></i>
                            <p class="small mt-2 mb-0">Aucun avis pour le moment</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar-large {
    width: 150px;
    height: 150px;
    margin: 0 auto;
}

.profile-avatar-large img,
.profile-avatar-large > div {
    width: 150px;
    height: 150px;
    font-size: 3rem;
}

.profile-name {
    font-size: 2.5rem;
    font-weight: 700;
    color: #000033;
    margin-bottom: 0.5rem;
}

.profile-title {
    font-size: 1.5rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
}

.stat-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 153, 255, 0.15);
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #000033;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.stat-sublabel {
    font-size: 0.8rem;
    color: #adb5bd;
}

.competences-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.competence-tag {
    background: linear-gradient(135deg, #0099FF, #00ccff);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: transform 0.3s ease;
}

.competence-tag:hover {
    transform: scale(1.05);
}

.categories-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.category-badge {
    background: linear-gradient(135deg, #000033, #0099FF);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 153, 255, 0.2);
}

.category-badge:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 153, 255, 0.3);
}

.formation-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.formation-item:last-child {
    border-bottom: none;
}

.experience-item {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    border-left: 4px solid #0099FF;
}

.social-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.social-link {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    background: #f8f9fa;
    color: #495057;
    text-decoration: none;
    border-radius: 20px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.social-link:hover {
    color: white;
    transform: translateY(-2px);
}

.social-link.linkedin:hover { background: #0077b5; }
.social-link.github:hover { background: #333; }
.social-link.portfolio:hover { background: #6f42c1; }
.social-link.website:hover { background: #28a745; }

.social-link i {
    margin-right: 0.5rem;
}

.cv-download-section {
    margin-top: 1rem;
}

.langues-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.langue-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.niveau-badge {
    background: #f8f9fa;
    color: #495057;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.portfolio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.portfolio-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.portfolio-item:hover {
    transform: scale(1.05);
}

.portfolio-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    color: white;
    font-size: 1.5rem;
}

.portfolio-item:hover .portfolio-overlay {
    opacity: 1;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.review-item-compact {
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.review-item-compact:last-child {
    border-bottom: none;
}

.verification-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.cv-download {
    padding: 2rem;
}

@media (max-width: 768px) {
    .profile-meta {
        flex-direction: column;
        gap: 0.75rem;
    }

    .profile-name {
        font-size: 2rem;
    }

    .profile-title {
        font-size: 1.25rem;
    }
}
</style>

<script>
function contactUser(userId) {
    // Rediriger vers la messagerie avec l'utilisateur sélectionné
    const userType = '<?= $_SESSION['user_type'] ?? '' ?>';
    let messageUrl = '';
    
    if (userType === 'candidat' || userType === 'prestataire_candidat') {
        messageUrl = '/lulu/views/candidat/messages.php?contact=' + userId;
    } else if (userType === 'prestataire') {
        messageUrl = '/lulu/views/prestataire/messages.php?contact=' + userId;
    } else if (userType === 'recruteur') {
        messageUrl = '/lulu/views/recruteur/messages.php?contact=' + userId;
    }
    
    if (messageUrl) {
        window.location.href = messageUrl;
    }
}

function toggleCVPreview() {
    const container = document.getElementById('cvPreviewContainer');
    const btn = document.getElementById('cvPreviewBtn');
    const frame = document.getElementById('cvPreviewFrame');
    
    if (container.style.display === 'none') {
        frame.src = 'uploads/cv/<?= $profile['cv_file'] ?? '' ?>';
        container.style.display = 'block';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i> Masquer';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-secondary');
    } else {
        container.style.display = 'none';
        frame.src = '';
        btn.innerHTML = '<i class="bi bi-eye"></i> Prévisualiser';
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-outline-primary');
    }
}

function toggleFavorite(profileId) {
    const btn = document.getElementById('favoriteBtn');
    const text = document.getElementById('favoriteText');

    if (text.textContent.includes('Ajouter')) {
        text.textContent = 'Retirer des favoris';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-danger');
    } else {
        text.textContent = 'Ajouter aux favoris';
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-outline-secondary');
    }
}

function shareProfile() {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Lien copié dans le presse-papiers !');
    }
}

function openReviewModal() {
    alert('Fonctionnalité d\'avis à implémenter');
}

function showAllReviews() {
    alert('Affichage de tous les avis à implémenter');
}

function openPortfolioModal(image, description) {
    alert('Portfolio: ' + description);
}

function togglePreview() {
    const pdfViewer = document.getElementById('pdfViewer');
    const previewBtn = document.getElementById('previewBtn');

    if (pdfViewer.style.display === 'none') {
        pdfViewer.style.display = 'block';
        previewBtn.innerHTML = '<i class="bi bi-eye-slash"></i> Masquer';
        previewBtn.classList.remove('btn-outline-primary');
        previewBtn.classList.add('btn-secondary');
    } else {
        pdfViewer.style.display = 'none';
        previewBtn.innerHTML = '<i class="bi bi-eye"></i> Prévisualiser';
        previewBtn.classList.remove('btn-secondary');
        previewBtn.classList.add('btn-outline-primary');
    }
}
</script>

<?php include 'includes/footer.php'; ?>

</body>
</html>
