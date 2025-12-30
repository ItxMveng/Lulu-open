<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_client();

$prestataire_id = $_GET['id'] ?? 0;

// Récupérer les infos du prestataire
global $database;
$sql = "SELECT u.id, u.nom, u.prenom, u.email, u.telephone, u.photo_profil, u.devise,
               l.ville, l.pays,
               p.titre_professionnel, p.description_services, p.note_moyenne, p.tarif_horaire,
               p.disponibilite,
               c.nom as categorie_nom
        FROM utilisateurs u
        JOIN profils_prestataires p ON u.id = p.utilisateur_id
        LEFT JOIN categories_services c ON p.categorie_id = c.id
        LEFT JOIN localisations l ON u.localisation_id = l.id
        WHERE u.id = ? AND u.statut = 'actif'";

$prestataire = $database->fetch($sql, [$prestataire_id]);

if (!$prestataire) {
    header('Location: recherche-prestataire.php');
    exit;
}

// Enregistrer la consultation
$database->query(
    "INSERT INTO historique_consultations (utilisateur_id, cible_type, cible_id, date_consultation) 
     VALUES (?, 'prestataire', ?, NOW())",
    [$_SESSION['user_id'], $prestataire_id]
);

$devise = $prestataire['devise'] ?? 'EUR';
$symbole = $devise === 'USD' ? '$' : ($devise === 'GBP' ? '£' : '€');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($prestataire['prenom'] . ' ' . $prestataire['nom'], ENT_QUOTES, 'UTF-8') ?> - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
        }
        body { background: #f8f9fa; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border: none; }
        .profile-header { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; padding: 3rem 0; }
        .profile-avatar { width: 150px; height: 150px; object-fit: cover; border: 5px solid white; }
        .rating-stars { color: #ffc107; font-size: 1.5rem; }
        .btn-primary-custom { background: linear-gradient(135deg, #000033 0%, #0099FF 100%); border: none; color: white; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="recherche-prestataire.php">Recherche</a></li>
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
                    $photoPath = $prestataire['photo_profil'] ? '/lulu/uploads/profiles/' . basename($prestataire['photo_profil']) : '';
                    if ($prestataire['photo_profil'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $photoPath)): 
                    ?>
                        <img src="<?= $photoPath ?>" class="rounded-circle profile-avatar" alt="Avatar">
                    <?php else: ?>
                        <div class="rounded-circle profile-avatar bg-white text-primary d-flex align-items-center justify-content-center" style="font-size: 3rem; font-weight: bold;">
                            <?= strtoupper(mb_substr($prestataire['prenom'], 0, 1) . mb_substr($prestataire['nom'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <h1 class="mb-2"><?= htmlspecialchars($prestataire['prenom'] . ' ' . $prestataire['nom'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <h4 class="mb-3"><?= htmlspecialchars($prestataire['titre_professionnel'], ENT_QUOTES, 'UTF-8') ?></h4>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="badge bg-light text-dark fs-6"><?= htmlspecialchars($prestataire['categorie_nom'], ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($prestataire['ville'] || $prestataire['pays']): ?>
                            <span><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars(($prestataire['ville'] ?? '') . ($prestataire['ville'] && $prestataire['pays'] ? ', ' : '') . ($prestataire['pays'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="rating-stars mb-3">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star-fill <?= $i <= $prestataire['note_moyenne'] ? '' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-2"><?= number_format($prestataire['note_moyenne'], 1) ?>/5</span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="text-center">
                        <h2 class="mb-0"><?= number_format($prestataire['tarif_horaire'], 0, ',', ' ') ?> <?= $symbole ?></h2>
                        <p class="mb-0">par heure</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="container my-5">
        <div class="row g-4">
            <!-- Colonne principale -->
            <div class="col-lg-8">
                <!-- Description -->
                <div class="card-custom p-4 mb-4" data-aos="fade-up">
                    <h3 class="mb-3" style="color: var(--primary-dark);"><i class="bi bi-file-text me-2"></i>Description</h3>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($prestataire['description_services'] ?? 'Aucune description disponible.', ENT_QUOTES, 'UTF-8')) ?></p>
                </div>


            </div>

            <!-- Colonne latérale -->
            <div class="col-lg-4">
                <!-- Actions -->
                <div class="card-custom p-4 mb-4" data-aos="fade-up">
                    <h5 class="mb-3">Actions</h5>
                    <a href="conversation.php?id=<?= $prestataire['id'] ?>" class="btn btn-primary-custom w-100 mb-2">
                        <i class="bi bi-chat-dots me-2"></i>Envoyer un message
                    </a>
                    <button class="btn btn-outline-danger w-100" id="btnFavorite" onclick="toggleFavorite(<?= $prestataire['id'] ?>)">
                        <i class="bi bi-heart me-2"></i><span id="favText">Ajouter aux favoris</span>
                    </button>
                </div>

                <!-- Informations -->
                <div class="card-custom p-4" data-aos="fade-up">
                    <h5 class="mb-3">Informations</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="bi bi-check-circle text-success me-2"></i>
                            <strong>Disponibilité :</strong> <?= $prestataire['disponibilite'] ? 'Disponible' : 'Non disponible' ?>
                        </li>
                        <?php if ($prestataire['telephone']): ?>
                        <li class="mb-2">
                            <i class="bi bi-telephone me-2"></i>
                            <strong>Téléphone :</strong> <?= htmlspecialchars($prestataire['telephone'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                        <?php endif; ?>
                        <li class="mb-2">
                            <i class="bi bi-envelope me-2"></i>
                            <strong>Email :</strong> <?= htmlspecialchars($prestataire['email'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
        
        let isFavorite = false;
        
        // Vérifier si déjà en favoris au chargement
        fetch(`/lulu/api/favorites?action=check&cible_id=<?= $prestataire['id'] ?>`)
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
                // Retirer des favoris
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
                })
                .catch(() => {
                    btn.disabled = false;
                    showToast('Erreur', 'error');
                });
            } else {
                // Ajouter aux favoris
                fetch('/lulu/api/favorites?action=add', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        cible_id: id,
                        type_cible: 'prestataire'
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
                })
                .catch(() => {
                    btn.disabled = false;
                    showToast('Erreur', 'error');
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
