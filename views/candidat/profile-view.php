<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';

$candidat_id = $_GET['id'] ?? null;
if (!$candidat_id) {
    header('Location: ../../index.php');
    exit;
}

global $database;

// Récupérer les informations du candidat
$candidat = $database->fetch("
    SELECT u.*, cv.* 
    FROM utilisateurs u 
    LEFT JOIN cvs cv ON u.id = cv.utilisateur_id 
    WHERE u.id = ? AND u.type_utilisateur IN ('candidat', 'prestataire_candidat')
", [$candidat_id]);

if (!$candidat) {
    header('Location: ../../index.php');
    exit;
}

// Parser les données structurées
$competences = $candidat['competences'] ? explode(', ', $candidat['competences']) : [];
$formations = $candidat['formations'] ? explode("\n", $candidat['formations']) : [];

$experiences = [];
if ($candidat['experiences_professionnelles']) {
    $exp_blocks = explode('---', $candidat['experiences_professionnelles']);
    foreach ($exp_blocks as $block) {
        $lines = array_filter(explode("\n", trim($block)));
        if (count($lines) >= 4) {
            $experiences[] = [
                'poste' => $lines[0],
                'entreprise' => $lines[1],
                'periode' => $lines[2],
                'lieu' => $lines[3],
                'description' => implode("\n", array_slice($lines, 4))
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']) ?> - Profil Candidat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            color: white;
            padding: 3rem 0;
        }
        .profile-card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 2rem;
        }
        .competence-badge {
            background: #e3f2fd;
            color: #0099ff;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            margin: 0.25rem;
            display: inline-block;
            font-size: 0.9rem;
        }
        .experience-card {
            border-left: 4px solid #0099ff;
            background: #f8f9fa;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-radius: 0 10px 10px 0;
        }
        .social-links a {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem;
            background: #f8f9fa;
            color: #0099ff;
            text-decoration: none;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        .social-links a:hover {
            background: #0099ff;
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <?php if ($candidat['photo_profil']): ?>
                        <img src="../uploads/profiles/<?= $candidat['photo_profil'] ?>" 
                             class="rounded-circle border border-white border-3" 
                             width="150" height="150" style="object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-white rounded-circle d-flex align-items-center justify-content-center mx-auto" 
                             style="width: 150px; height: 150px; color: #0099ff; font-size: 3rem;">
                            <?= strtoupper(substr($candidat['prenom'], 0, 1) . substr($candidat['nom'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h1 class="display-5 mb-2"><?= htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom']) ?></h1>
                    <h3 class="h4 mb-3 opacity-75"><?= htmlspecialchars($candidat['titre_poste_recherche'] ?? 'Candidat') ?></h3>
                    
                    <div class="row g-3">
                        <?php if ($candidat['email']): ?>
                            <div class="col-auto">
                                <i class="bi bi-envelope me-2"></i><?= htmlspecialchars($candidat['email']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($candidat['telephone']): ?>
                            <div class="col-auto">
                                <i class="bi bi-telephone me-2"></i><?= htmlspecialchars($candidat['telephone']) ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($candidat['niveau_experience']): ?>
                            <div class="col-auto">
                                <i class="bi bi-award me-2"></i><?= ucfirst($candidat['niveau_experience']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-lg-8">
                <!-- Compétences -->
                <?php if (!empty($competences)): ?>
                    <div class="profile-card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">💻 Compétences techniques</h5>
                            <div>
                                <?php foreach ($competences as $competence): ?>
                                    <span class="competence-badge"><?= htmlspecialchars(trim($competence)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Expériences -->
                <?php if (!empty($experiences)): ?>
                    <div class="profile-card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">💼 Expériences professionnelles</h5>
                            <?php foreach ($experiences as $exp): ?>
                                <div class="experience-card">
                                    <h6 class="fw-bold text-primary"><?= htmlspecialchars($exp['poste']) ?></h6>
                                    <div class="text-muted mb-2">
                                        <strong><?= htmlspecialchars($exp['entreprise']) ?></strong> • 
                                        <?= htmlspecialchars($exp['periode']) ?> • 
                                        <?= htmlspecialchars($exp['lieu']) ?>
                                    </div>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($exp['description'])) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formations -->
                <?php if (!empty($formations)): ?>
                    <div class="profile-card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">🎓 Formations & Diplômes</h5>
                            <?php foreach ($formations as $formation): ?>
                                <?php if (trim($formation)): ?>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-mortarboard text-primary me-3"></i>
                                        <span><?= htmlspecialchars(trim($formation)) ?></span>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Informations de contact -->
                <div class="profile-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">📞 Contact</h5>
                        
                        <?php if (isLoggedIn()): ?>
                            <div class="d-grid gap-2">
                                <a href="../candidat/messages.php?contact=<?= $candidat['id'] ?>" class="btn btn-primary">
                                    <i class="bi bi-envelope me-2"></i>Envoyer un message
                                </a>
                                <button class="btn btn-outline-primary" onclick="addToFavorites(<?= $candidat['id'] ?>)">
                                    <i class="bi bi-heart me-2"></i>Ajouter aux favoris
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <a href="../../login.php">Connectez-vous</a> pour contacter ce candidat
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- CV et liens -->
                <div class="profile-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">📄 Documents & Liens</h5>
                        
                        <?php if ($candidat['cv_file']): ?>
                            <div class="mb-3">
                                <a href="../uploads/cv/<?= $candidat['cv_file'] ?>" target="_blank" class="btn btn-outline-success w-100">
                                    <i class="bi bi-file-pdf me-2"></i>Télécharger le CV
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="social-links">
                            <?php if ($candidat['linkedin']): ?>
                                <a href="<?= htmlspecialchars($candidat['linkedin']) ?>" target="_blank">
                                    <i class="bi bi-linkedin me-2"></i>LinkedIn
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($candidat['github']): ?>
                                <a href="<?= htmlspecialchars($candidat['github']) ?>" target="_blank">
                                    <i class="bi bi-github me-2"></i>GitHub
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($candidat['portfolio']): ?>
                                <a href="<?= htmlspecialchars($candidat['portfolio']) ?>" target="_blank">
                                    <i class="bi bi-briefcase me-2"></i>Portfolio
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($candidat['site_web']): ?>
                                <a href="<?= htmlspecialchars($candidat['site_web']) ?>" target="_blank">
                                    <i class="bi bi-globe me-2"></i>Site web
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Informations supplémentaires -->
                <div class="profile-card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">ℹ️ Informations</h5>
                        
                        <?php if ($candidat['type_contrat']): ?>
                            <div class="mb-2">
                                <strong>Type de contrat :</strong> <?= ucfirst($candidat['type_contrat']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($candidat['salaire_souhaite']): ?>
                            <div class="mb-2">
                                <strong>Salaire souhaité :</strong> <?= number_format($candidat['salaire_souhaite'], 0, ',', ' ') ?> €/an
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-2">
                            <strong>Profil créé :</strong> <?= date('d/m/Y', strtotime($candidat['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addToFavorites(candidatId) {
            // Implémentation pour ajouter aux favoris
            fetch('../../api/add-favorite.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({candidat_id: candidatId})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Candidat ajouté aux favoris !');
                }
            });
        }
    </script>
</body>
</html>