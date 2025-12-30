<?php
require_once __DIR__ . '/../../includes/middleware.php';
require_client();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../models/Notification.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!isset($_SESSION['user_id']) || $_SESSION['type_utilisateur'] !== 'client') {
    redirect('login.php');
}

$notifModel = new Notification();

if (isset($_GET['action']) && $_GET['action'] === 'mark_all_read') {
    $notifModel->markAllAsRead($_SESSION['user_id']);
    redirect('views/client/notifications.php');
}

$page = $_GET['page'] ?? 1;
$notifications = $notifModel->getAll($_SESSION['user_id'], $page, 20);
$unreadCount = $notifModel->countUnread($_SESSION['user_id']);
$page_title = "Notifications - LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root { --primary-dark: #000033; --primary-blue: #0099FF; }
        body { background: #f8f9fa; }
        .notification-item { border: none; border-bottom: 1px solid #dee2e6; padding: 1.5rem 1rem; transition: all 0.3s; }
        .notification-item.unread { background: rgba(0, 153, 255, 0.05); border-left: 4px solid var(--primary-blue); }
        .notification-item:hover { background: #f8f9fa; transform: translateX(5px); }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar-client.php'; ?>
    
    <div class="container mt-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= 'dashboard.php' ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Notifications</li>
            </ol>
        </nav>
    </div>

    <main class="container my-4">
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <div>
                    <h1 style="color: var(--primary-dark);">
                        <i class="bi bi-bell-fill me-2"></i>Notifications
                    </h1>
                    <p class="text-muted"><?= $unreadCount ?> notification(s) non lue(s)</p>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <a href="?action=mark_all_read" class="btn btn-outline-primary">
                        <i class="bi bi-check2-all"></i> Tout marquer comme lu
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5">
                <i class="bi bi-bell-slash" style="font-size: 5rem; color: #ccc;"></i>
                <h3 class="mt-3">Aucune notification</h3>
                <p class="text-muted">Vous êtes à jour !</p>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <div class="list-group">
                        <?php foreach ($notifications as $notif): ?>
                            <div class="list-group-item notification-item <?= $notif['lu'] ? '' : 'unread' ?>"
                                 data-notification-id="<?= $notif['id'] ?>">
                                <div class="d-flex align-items-start">
                                    <div class="me-3">
                                        <?php
                                        $iconClass = match($notif['type_notification']) {
                                            'message' => 'bi-envelope-fill',
                                            'favori' => 'bi-heart-fill',
                                            'avis' => 'bi-star-fill',
                                            default => 'bi-bell-fill'
                                        };
                                        $iconColor = match($notif['type_notification']) {
                                            'message' => '#0099FF',
                                            'favori' => '#FF3366',
                                            'avis' => '#FFD700',
                                            default => '#6c757d'
                                        };
                                        ?>
                                        <i class="bi <?= $iconClass ?>" style="font-size: 2rem; color: <?= $iconColor ?>;"></i>
                                    </div>
                                    
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h6 class="mb-0 <?= !$notif['lu'] ? 'fw-bold' : '' ?>">
                                                <?= htmlspecialchars($notif['titre'], ENT_QUOTES, 'UTF-8') ?>
                                            </h6>
                                            <small class="text-muted"><?= time_ago($notif['created_at']) ?></small>
                                        </div>
                                        <p class="mb-2 text-muted"><?= htmlspecialchars($notif['contenu'], ENT_QUOTES, 'UTF-8') ?></p>
                                        
                                        <div class="d-flex gap-2">
                                            <?php if ($notif['url_action']): ?>
                                                <a href="<?= $notif['url_action'] ?>" class="btn btn-sm btn-primary">Voir</a>
                                            <?php endif; ?>
                                            <?php if (!$notif['lu']): ?>
                                                <button class="btn btn-sm btn-outline-secondary mark-read" data-id="<?= $notif['id'] ?>">
                                                    <i class="bi bi-check2"></i> Marquer lu
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-danger delete-notif" data-id="<?= $notif['id'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/notifications.js') ?>"></script>
</body>
</html>
