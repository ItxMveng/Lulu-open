<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_client();

$candidat_id = $_GET['id'] ?? 0;

// Récupérer les infos du candidat
$sql = "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.photo_profil,
               l.ville, l.pays,
               cv.titre_poste_recherche, cv.experience_annees, cv.competences, cv.formations,
               cv.langues, cv.disponibilite_immediate, cv.salaire_souhaite
        FROM utilisateurs u
        JOIN cvs cv ON u.id = cv.utilisateur_id
        LEFT JOIN localisations l ON u.localisation_id = l.id
        WHERE u.id = ? AND u.statut = 'actif'";

$candidat = $database->fetch($sql, [$candidat_id]);

if (!$candidat) {
    header('Location: recherche-candidat.php');
    exit;
}

// Enregistrer la consultation
$database->query(
    "INSERT INTO historique_consultations (utilisateur_id, cible_type, cible_id, date_consultation) 
     VALUES (?, 'candidat', ?, NOW())",
    [$_SESSION['user_id'], $candidat_id]
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom'], ENT_QUOTES, 'UTF-8') ?> - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background: #f8f9fa; margin: 0; padding: 0; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: none; }
        .profile-header { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; padding: 3rem 0; }
        .profile-avatar { width: 150px; height: 150px; object-fit: cover; border: 5px solid white; }
        .avatar-initials { width: 150px; height: 150px; background: white; color: #0099FF; font-size: 3rem; font-weight: bold; border: 5px solid white; }
        .btn-primary-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); border: none; color: white; border-radius: 25px; padding: 0.5rem 1.5rem; }
        .skill-badge { background: #e7f3ff; color: #0d6efd; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.9rem; margin: 0.25rem; display: inline-block; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="recherche-candidat.php">Recherche</a></li>
                <li class="breadcrumb-item active">Profil</li>
            </ol>
        </nav>
    </div>

    <!-- En-tête profil -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-auto">
                    <?php 
                    $photoPath = $candidat['photo_profil'] ? '/lulu/uploads/profiles/' . basename($candidat['photo_profil']) : '';
                    if ($candidat['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)): 
                    ?>
                        <img src="<?= $photoPath ?>" class="rounded-circle profile-avatar" alt="Avatar">
                    <?php else: 
                        $initials = mb_substr($candidat['prenom'], 0, 1) . mb_substr($candidat['nom'], 0, 1);
                    ?>
                        <div class="rounded-circle avatar-initials d-flex align-items-center justify-content-center">
                            <?= strtoupper($initials) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <h1 class="mb-2"><?= htmlspecialchars($candidat['prenom'] . ' ' . $candidat['nom'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <h4 class="mb-3"><?= htmlspecialchars($candidat['titre_poste_recherche'] ?? 'Candidat', ENT_QUOTES, 'UTF-8') ?></h4>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="badge bg-light text-dark fs-6"><?= $candidat['experience_annees'] ?? 0 ?> ans d'expérience</span>
                        <?php if ($candidat['ville'] || $candidat['pays']): ?>
                            <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(trim(($candidat['ville'] ?? '') . ($candidat['ville'] && $candidat['pays'] ? ', ' : '') . ($candidat['pays'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <span class="badge" style="background: rgba(255,255,255,0.2); font-size: 1rem;">
                            <i class="bi bi-calendar-check me-1"></i>
                            Disponibilité: <?= $candidat['disponibilite_immediate'] ? 'Immédiate' : 'Sur demande' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="container my-5">
        <div class="row g-4">
            <!-- Colonne principale -->
            <div class="col-lg-8">
                <!-- Compétences -->
                <?php if ($candidat['competences']): ?>
                <div class="card-custom p-4 mb-4">
                    <h3 class="mb-3" style="color: #000033;"><i class="bi bi-star me-2"></i>Compétences</h3>
                    <div>
                        <?php 
                        $skills = explode(',', $candidat['competences']);
                        foreach ($skills as $skill): 
                        ?>
                            <span class="skill-badge"><?= htmlspecialchars(trim($skill), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Formations -->
                <?php if ($candidat['formations']): ?>
                <div class="card-custom p-4 mb-4">
                    <h3 class="mb-3" style="color: #000033;"><i class="bi bi-mortarboard me-2"></i>Formations</h3>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($candidat['formations'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
                <?php endif; ?>

                <!-- Langues -->
                <?php if ($candidat['langues']): ?>
                <div class="card-custom p-4 mb-4">
                    <h3 class="mb-3" style="color: #000033;"><i class="bi bi-translate me-2"></i>Langues</h3>
                    <p class="text-muted"><?= htmlspecialchars($candidat['langues'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Colonne latérale -->
            <div class="col-lg-4">
                <!-- Actions -->
                <div class="card-custom p-4 mb-4">
                    <h5 class="mb-3">Actions</h5>
                    <a href="conversation.php?id=<?= $candidat['id'] ?>" class="btn btn-primary-custom w-100 mb-2">
                        <i class="bi bi-chat-dots me-2"></i>Envoyer un message
                    </a>
                    <button class="btn btn-outline-danger w-100" id="btnFavorite" onclick="toggleFavorite(<?= $candidat['id'] ?>)" style="border-radius: 25px;">
                        <i class="bi bi-heart me-2"></i><span id="favText">Ajouter aux favoris</span>
                    </button>
                </div>

                <!-- Informations -->
                <div class="card-custom p-4 mb-4">
                    <h5 class="mb-3">Informations</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-briefcase me-2" style="color: #0099FF;"></i>
                            <strong>Expérience:</strong> <?= $candidat['experience_annees'] ?? 0 ?> ans
                        </li>
                        <?php if ($candidat['salaire_souhaite']): ?>
                        <li class="mb-2">
                            <i class="bi bi-cash me-2" style="color: #0099FF;"></i>
                            <strong>Salaire souhaité:</strong> <?= htmlspecialchars($candidat['salaire_souhaite'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                        <?php endif; ?>
                        <?php if ($candidat['telephone']): ?>
                        <li class="mb-2">
                            <i class="bi bi-telephone me-2" style="color: #0099FF;"></i>
                            <strong>Téléphone:</strong> <?= htmlspecialchars($candidat['telephone'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <i class="bi bi-envelope me-2" style="color: #0099FF;"></i>
                            <strong>Email:</strong> <?= htmlspecialchars($candidat['email'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let isFavorite = false;
        
        // Vérifier si déjà en favoris
        fetch(`/lulu/api/favorites?action=check&cible_id=<?= $candidat['id'] ?>`)
            .then(r => r.json())
            .then(data => {
                if (data.is_favorite) {
                    isFavorite = true;
                    updateFavoriteButton();
                }
            });
        
        function toggleFavorite(id) {
            const btn = document.getElementById('btnFavorite');
            btn.disabled = true;
            
            if (isFavorite) {
                fetch(`/lulu/api/favorites?action=remove&cible_id=${id}`, {
                    method: 'DELETE'
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'removed') {
                        isFavorite = false;
                        updateFavoriteButton();
                        showToast('Retiré des favoris', 'info');
                    }
                    btn.disabled = false;
                });
            } else {
                fetch('/lulu/api/favorites?action=add', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        cible_id: id,
                        type_cible: 'candidat'
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'added' || data.status === 'already_added') {
                        isFavorite = true;
                        updateFavoriteButton();
                        showToast('Ajouté aux favoris', 'success');
                    }
                    btn.disabled = false;
                });
            }
        }
        
        function updateFavoriteButton() {
            const btn = document.getElementById('btnFavorite');
            const text = document.getElementById('favText');
            if (isFavorite) {
                btn.classList.remove('btn-outline-danger');
                btn.classList.add('btn-danger');
                text.textContent = 'Retirer des favoris';
            } else {
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline-danger');
                text.textContent = 'Ajouter aux favoris';
            }
        }
        
        function showToast(message, type) {
            const colors = {success: '#28a745', error: '#dc3545', info: '#17a2b8'};
            const toast = document.createElement('div');
            toast.style.cssText = `position:fixed;top:20px;right:20px;background:${colors[type]};color:white;padding:15px 20px;border-radius:8px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.3);`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }
    </script>
</body>
</html>
