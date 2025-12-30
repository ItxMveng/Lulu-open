<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/ClientController.php';

require_client();

$controller = new ClientController();
$stats = $controller->getStats($_SESSION['user_id']);
$notifications = $controller->getRecentNotifications($_SESSION['user_id'], 5);

// Récupérer photo de profil
$user = $database->fetch("SELECT photo_profil, prenom, nom FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
$user_photo = null;
if ($user && $user['photo_profil']) {
    $photo_path = '/lulu/uploads/profiles/' . basename($user['photo_profil']);
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . $photo_path)) {
        $user_photo = $photo_path;
    }
}

$page_title = "Dashboard Client - LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
            --gradient-primary: linear-gradient(135deg, #000033 0%, #0099FF 100%);
        }
        body { background: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card-custom { background: white; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: all 0.3s; }
        .card-custom:hover { box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .stat-card { position: relative; overflow: hidden; cursor: pointer; }
        .stat-card:hover { transform: translateY(-5px); }
        .btn-primary-custom { background: var(--gradient-primary); border: none; color: white; padding: 10px 25px; border-radius: 25px; }
        .btn-outline-custom { border: 2px solid var(--primary-blue); color: var(--primary-blue); border-radius: 25px; padding: 10px 25px; }
        .btn-outline-custom:hover { background: var(--primary-blue); color: white; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>

    <main class="container my-4">
        <!-- En-tête -->
        <div class="row mb-4" data-aos="fade-down">
            <div class="col-12">
                <div class="card-custom p-4">
                    <div class="d-flex align-items-center">
                        <?php if ($user_photo): ?>
                            <img src="<?= $user_photo ?>" 
                                 alt="Photo" class="rounded-circle me-3" 
                                 style="width: 80px; height: 80px; object-fit: cover;">
                        <?php else: 
                            $initials = mb_substr($user['prenom'] ?? 'C', 0, 1) . mb_substr($user['nom'] ?? 'L', 0, 1);
                        ?>
                            <div class="rounded-circle me-3 d-inline-flex align-items-center justify-content-center"
                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #000033 0%, #0099FF 100%); color: white; font-size: 2rem; font-weight: bold;">
                                <?= strtoupper($initials) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="mb-1" style="color: var(--primary-dark);">
                                Bonjour, <?= htmlspecialchars($user['prenom'] ?? 'Client', ENT_QUOTES, 'UTF-8') ?> ! 👋
                            </h2>
                            <p class="text-muted mb-0">
                                <i class="bi bi-clock"></i> Dernière connexion : <?= date('d/m/Y à H:i') ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistiques -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <a href="favorites.php" class="text-decoration-none">
                    <div class="card-custom text-center p-4 stat-card">
                        <i class="bi bi-heart-fill" style="font-size: 3rem; color: #FF3366;"></i>
                        <h3 class="mt-3 mb-1" style="color: var(--primary-dark);"><?= $stats['favoris'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Favoris</p>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <a href="messages.php" class="text-decoration-none">
                    <div class="card-custom text-center p-4 stat-card position-relative">
                        <i class="bi bi-envelope-fill" style="font-size: 3rem; color: #0099FF;"></i>
                        <?php if (($stats['messages_non_lus'] ?? 0) > 0): ?>
                            <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                                <?= $stats['messages_non_lus'] ?>
                            </span>
                        <?php endif; ?>
                        <h3 class="mt-3 mb-1" style="color: var(--primary-dark);"><?= $stats['messages_non_lus'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Messages non lus</p>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <a href="activity.php" class="text-decoration-none">
                    <div class="card-custom text-center p-4 stat-card">
                        <i class="bi bi-eye-fill" style="font-size: 3rem; color: #00ccff;"></i>
                        <h3 class="mt-3 mb-1" style="color: var(--primary-dark);"><?= $stats['consultations_7j'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Profils vus (7j)</p>
                    </div>
                </a>
            </div>
            
            <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
                <div class="card-custom text-center p-4 stat-card position-relative">
                    <i class="bi bi-bell-fill" style="font-size: 3rem; color: #FFD700;"></i>
                    <?php if (($stats['notifications_non_lues'] ?? 0) > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 end-0 m-2">
                            <?= $stats['notifications_non_lues'] ?>
                        </span>
                    <?php endif; ?>
                    <h3 class="mt-3 mb-1" style="color: var(--primary-dark);"><?= $stats['notifications_non_lues'] ?? 0 ?></h3>
                    <p class="text-muted mb-0">Notifications</p>
                </div>
            </div>
        </div>
        
        <!-- Actions rapides -->
        <div class="row mb-4">
            <div class="col-12" data-aos="fade-up">
                <h3 class="mb-3" style="color: var(--primary-dark);">
                    <i class="bi bi-lightning-fill me-2"></i>Actions rapides
                </h3>
                <div class="row g-3">
                    <div class="col-lg-3 col-md-6">
                        <a href="recherche-prestataire.php" class="btn btn-primary-custom w-100 py-3">
                            <i class="bi bi-search me-2"></i>Chercher prestataire
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="recherche-candidat.php" class="btn btn-outline-custom w-100 py-3">
                            <i class="bi bi-file-earmark-person me-2"></i>Chercher candidat
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="favorites.php" class="btn btn-outline-custom w-100 py-3">
                            <i class="bi bi-heart me-2"></i>Mes favoris
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <a href="activity.php" class="btn btn-outline-custom w-100 py-3">
                            <i class="bi bi-clock-history me-2"></i>Historique
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notifications récentes -->
        <?php if (!empty($notifications)): ?>
        <div class="row">
            <div class="col-12" data-aos="fade-up">
                <div class="card-custom">
                    <div class="p-3 d-flex justify-content-between align-items-center" style="background: var(--gradient-primary); color: white; border-radius: 15px 15px 0 0;">
                        <h4 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications récentes</h4>
                        <a href="#" class="btn btn-sm btn-light">Voir tout</a>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notifications as $notif): ?>
                        <div class="list-group-item <?= $notif['lu'] ? '' : 'bg-light' ?>">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle me-3" style="font-size: 1.5rem; color: var(--primary-blue);"></i>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($notif['titre'], ENT_QUOTES, 'UTF-8') ?></h6>
                                    <p class="mb-0 text-muted small"><?= htmlspecialchars(substr($notif['contenu'], 0, 80, ENT_QUOTES, 'UTF-8')) ?>...</p>
                                    <small class="text-muted"><i class="bi bi-clock"></i> <?= time_ago($notif['created_at']) ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>AOS.init();</script>
</body>
</html>
