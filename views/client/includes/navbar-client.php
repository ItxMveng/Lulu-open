<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Récupérer compteurs et photo de profil
$unreadMessages = 0;
$unreadNotifications = 0;
$user_photo = null;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../../models/Message.php';
    require_once __DIR__ . '/../../../models/Notification.php';
    $messageModel = new Message();
    $notifModel = new Notification();
    $unreadMessages = $messageModel->countUnread($_SESSION['user_id']);
    $unreadNotifications = $notifModel->countUnread($_SESSION['user_id']);
    
    // Récupérer photo de profil
    global $database;
    $user_data = $database->fetch("SELECT photo_profil FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
    if ($user_data && $user_data['photo_profil']) {
        $photo_path = '/lulu/uploads/profiles/' . basename($user_data['photo_profil']);
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $photo_path)) {
            $user_photo = $photo_path;
        }
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(135deg, #000033 0%, #0099FF 100%); box-shadow: 0 4px 15px rgba(0,153,255,0.3);">
    <div class="container-fluid px-4">
        <!-- Logo -->
        <a class="navbar-brand fw-bold" href="dashboard.php" style="font-size: 1.3rem; color: white;">
            <i class="bi bi-briefcase-fill me-2"></i>LULU<span style="color: #00ccff;">-OPEN</span>
        </a>
        
        <!-- Toggle mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarClient">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarClient">
            <!-- Menu principal -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'favorites.php' ? 'active' : '' ?>" href="favorites.php">
                        <i class="bi bi-heart-fill"></i> Favoris
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative <?= $current_page === 'messages.php' ? 'active' : '' ?>" href="messages.php">
                        <i class="bi bi-chat-dots-fill"></i> Messages
                        <?php if ($unreadMessages > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $unreadMessages ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative <?= $current_page === 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
                        <i class="bi bi-bell-fill"></i> Notifications
                        <?php if ($unreadNotifications > 0): ?>
                            <span class="badge bg-warning rounded-pill ms-1"><?= $unreadNotifications ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <!-- Menu utilisateur -->
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <?php if ($user_photo): ?>
                            <img src="<?= $user_photo ?>" 
                                 alt="Avatar" 
                                 class="rounded-circle me-2"
                                 style="width: 32px; height: 32px; object-fit: cover; border: 2px solid white;">
                        <?php else: 
                            $initials = mb_substr($_SESSION['prenom'] ?? 'C', 0, 1) . mb_substr($_SESSION['nom'] ?? 'L', 0, 1);
                        ?>
                            <div class="rounded-circle me-2 d-inline-flex align-items-center justify-content-center"
                                 style="width: 32px; height: 32px; background: white; color: #0099FF; font-weight: bold; font-size: 0.8rem; border: 2px solid white;">
                                <?= strtoupper($initials) ?>
                            </div>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($_SESSION['prenom'] ?? 'Client', ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="activity.php"><i class="bi bi-clock-history me-2"></i>Mon Activité</a></li>
                        <li><a class="dropdown-item" href="saved-searches.php"><i class="bi bi-bookmark me-2"></i>Recherches</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Paramètres</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/lulu/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar-dark .nav-link {
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: all 0.3s;
    border-radius: 8px;
    margin: 0 0.2rem;
}
.navbar-dark .nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}
.navbar-dark .nav-link.active {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-weight: 600;
}
.dropdown-menu {
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    border: none;
}
.dropdown-item {
    padding: 0.6rem 1.2rem;
    transition: all 0.2s;
}
.dropdown-item:hover {
    background: #f8f9fa;
    padding-left: 1.5rem;
}
@media (max-width: 991px) {
    .navbar-nav { padding: 1rem 0; }
    .badge { position: relative !important; }
}
</style>
