<?php
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$isClient = $isLoggedIn && isset($_SESSION['type_utilisateur']) && $_SESSION['type_utilisateur'] === 'client';
$current_page = basename($_SERVER['PHP_SELF']);

// Récupérer compteurs notifications/messages si CLIENT connecté
$unreadMessages = 0;
$unreadNotifications = 0;
if ($isClient) {
    require_once __DIR__ . '/../models/Message.php';
    require_once __DIR__ . '/../models/Notification.php';
    $messageModel = new Message();
    $notifModel = new Notification();
    $unreadMessages = $messageModel->countUnread($_SESSION['user_id']);
    $unreadNotifications = $notifModel->countUnread($_SESSION['user_id']);
}
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(135deg, #000033 0%, #001a4d 100%); box-shadow: 0 4px 20px rgba(0, 153, 255, 0.3);">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold" href="<?= url('') ?>" style="font-size: 1.5rem; color: #0099FF;">
            <i class="bi bi-briefcase-fill me-2"></i>LULU<span style="color: white;">-OPEN</span>
        </a>
        
        <!-- Toggle mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- Menu principal -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="<?= url('') ?>">
                        <i class="bi bi-house-fill me-1"></i> Accueil
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('services.php') ?>">
                        <i class="bi bi-tools me-1"></i> Services
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('emplois.php') ?>">
                        <i class="bi bi-file-earmark-person me-1"></i> Emplois
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('about.php') ?>">
                        <i class="bi bi-info-circle me-1"></i> À propos
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('contact.php') ?>">
                        <i class="bi bi-envelope me-1"></i> Contact
                    </a>
                </li>
            </ul>

            <!-- Menu utilisateur -->
            <ul class="navbar-nav ms-auto">
                <?php if ($isClient): ?>
                    <!-- Menu CLIENT -->
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" 
                           href="<?= url('views/client/dashboard.php') ?>">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'favorites.php' ? 'active' : '' ?>" 
                           href="<?= url('views/client/favorites.php') ?>">
                            <i class="bi bi-heart-fill me-1"></i> Favoris
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link position-relative <?= $current_page === 'messages.php' ? 'active' : '' ?>" 
                           href="<?= url('views/client/messages.php') ?>">
                            <i class="bi bi-chat-dots-fill me-1"></i> Messages
                            <?php if ($unreadMessages > 0): ?>
                                <span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle">
                                    <?= $unreadMessages ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link position-relative <?= $current_page === 'notifications.php' ? 'active' : '' ?>" 
                           href="<?= url('views/client/notifications.php') ?>">
                            <i class="bi bi-bell-fill me-1"></i> Notifications
                            <?php if ($unreadNotifications > 0): ?>
                                <span class="badge bg-warning rounded-pill position-absolute top-0 start-100 translate-middle">
                                    <?= $unreadNotifications ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <!-- Dropdown Profil -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="<?= $_SESSION['photo_profil'] ?? url('assets/images/default-avatar.png') ?>" 
                                 alt="Avatar" 
                                 class="rounded-circle me-1"
                                 style="width: 30px; height: 30px; object-fit: cover;">
                            <?= htmlspecialchars($_SESSION['prenom'] ?? 'Client') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= url('views/client/activity.php') ?>">
                                    <i class="bi bi-clock-history me-2"></i>Mon Activité
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('views/client/saved-searches.php') ?>">
                                    <i class="bi bi-bookmark me-2"></i>Recherches Sauvegardées
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= url('views/client/settings.php') ?>">
                                    <i class="bi bi-gear me-2"></i>Paramètres
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= url('logout.php') ?>">
                                    <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                <?php elseif ($isLoggedIn): ?>
                    <!-- Autre rôle (prestataire/candidat/admin) -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('dashboard') ?>">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="<?= url('logout.php') ?>">
                            <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
                        </a>
                    </li>
                    
                <?php else: ?>
                    <!-- Visiteur non connecté -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= url('login.php') ?>">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Connexion
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link btn-inscription px-3 py-2 rounded-pill" 
                           href="<?= url('register.php') ?>"
                           style="background: linear-gradient(135deg, #0099FF, #00ccff); color: white; font-weight: 600; transition: all 0.3s;">
                            <i class="bi bi-person-plus me-1"></i> Inscription
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
.navbar-dark .nav-link {
    color: rgba(255, 255, 255, 0.85);
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
    position: relative;
}

.navbar-dark .nav-link:hover {
    color: #0099FF;
    transform: translateY(-2px);
}

.navbar-dark .nav-link.active {
    color: #0099FF;
    font-weight: 600;
}

.navbar-dark .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 3px;
    background: linear-gradient(90deg, transparent, #0099FF, transparent);
    border-radius: 10px;
}

.btn-inscription:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 153, 255, 0.5) !important;
}

.badge {
    font-size: 0.7rem;
    padding: 0.25em 0.5em;
}

@media (max-width: 991px) {
    .navbar-nav {
        padding: 1rem 0;
    }
    
    .nav-item {
        margin: 0.3rem 0;
    }
    
    .btn-inscription {
        margin-top: 1rem;
        text-align: center;
    }
    
    .badge {
        position: relative !important;
        transform: none !important;
        margin-left: 0.5rem;
    }
}
</style>
