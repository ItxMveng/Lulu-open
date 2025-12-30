<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(135deg, #000033 0%, #001a4d 100%); box-shadow: 0 4px 20px rgba(0, 153, 255, 0.3);">
    <div class="container-fluid">
        <!-- Logo ADMIN -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= url('views/admin/dashboard.php') ?>" style="font-size: 1.5rem;">
            <i class="bi bi-shield-fill-check me-2" style="color: #0099FF;"></i>
            <span style="color: #0099FF;">ADMIN</span>
            <span style="color: white; margin-left: 0.3rem;">LULU-OPEN</span>
        </a>
        
        <!-- Toggle mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarAdmin">
            <!-- Menu principal -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>" 
                       href="<?= url('views/admin/dashboard.php') ?>">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'users.php' ? 'active' : '' ?>" 
                       href="<?= url('views/admin/users.php') ?>">
                        <i class="bi bi-people-fill me-1"></i> Utilisateurs
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'validations.php' ? 'active' : '' ?>" 
                       href="<?= url('views/admin/validations.php') ?>">
                        <i class="bi bi-check-circle-fill me-1"></i> Validations
                        <?php
                        // Compteur demandes en attente
                        $db = Database::getInstance()->getConnection();
                        $stmt = $db->query("SELECT COUNT(*) FROM demandes_activation WHERE statut = 'en_attente'");
                        $pending = $stmt->fetchColumn();
                        if ($pending > 0):
                        ?>
                            <span class="badge bg-danger rounded-pill"><?= $pending ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-cash-stack me-1"></i> Finances
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= url('views/admin/subscriptions.php') ?>">
                            <i class="bi bi-award me-2"></i>Abonnements
                        </a></li>
                        <li><a class="dropdown-item" href="<?= url('views/admin/plans.php') ?>">
                            <i class="bi bi-card-list me-2"></i>Plans Tarifaires
                        </a></li>
                        <li><a class="dropdown-item" href="<?= url('views/admin/payments.php') ?>">
                            <i class="bi bi-credit-card me-2"></i>Paiements
                        </a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'statistics.php' ? 'active' : '' ?>" 
                       href="<?= url('views/admin/statistics.php') ?>">
                        <i class="bi bi-graph-up me-1"></i> Statistiques
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'settings.php' ? 'active' : '' ?>" 
                       href="<?= url('views/admin/settings.php') ?>">
                        <i class="bi bi-gear-fill me-1"></i> Paramètres
                    </a>
                </li>
            </ul>

            <!-- Menu utilisateur admin -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= url('') ?>" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Voir le site
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <img src="<?= $_SESSION['photo_profil'] ?? url('assets/images/default-avatar.png') ?>" 
                             alt="Avatar" 
                             class="rounded-circle me-2"
                             style="width: 30px; height: 30px; object-fit: cover;">
                        <span><?= htmlspecialchars($_SESSION['prenom']) ?></span>
                        <span class="badge bg-danger ms-2">ADMIN</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= url('views/admin/profile.php') ?>">
                            <i class="bi bi-person me-2"></i>Mon Profil
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>">
                            <i class="bi bi-box-arrow-right me-2"></i>Déconnexion
                        </a></li>
                    </ul>
                </li>
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
}

.navbar-dark .nav-link:hover {
    color: #0099FF;
    transform: translateY(-2px);
}

.navbar-dark .nav-link.active {
    color: #0099FF;
    font-weight: 600;
    position: relative;
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

.dropdown-menu {
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    border-radius: 10px;
}

.dropdown-item {
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background: linear-gradient(135deg, #0099FF, #00ccff);
    color: white;
    transform: translateX(5px);
}

.badge {
    font-size: 0.7rem;
    padding: 0.25em 0.6em;
}
</style>
