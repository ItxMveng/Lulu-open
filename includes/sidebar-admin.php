<?php
$current_page = basename($_SERVER['PHP_SELF']);

// Compteur demandes en attente
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT COUNT(*) FROM demandes_activation WHERE statut = 'en_attente'");
$pending = $stmt->fetchColumn();
?>

<!-- Sidebar Admin -->
<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="<?= url('views/admin/dashboard.php') ?>" class="sidebar-brand">
            <i class="bi bi-shield-fill-check"></i>
            <span class="brand-text">ADMIN</span>
        </a>
        <button class="sidebar-toggle d-lg-none" onclick="toggleSidebar()">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="sidebar-menu">
        <a href="<?= url('views/admin/dashboard.php') ?>" class="menu-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= url('views/admin/users.php') ?>" class="menu-item <?= $current_page === 'users.php' ? 'active' : '' ?>">
            <i class="bi bi-people-fill"></i>
            <span>Utilisateurs</span>
        </a>

        <a href="<?= url('views/admin/messages.php') ?>" class="menu-item <?= $current_page === 'messages.php' ? 'active' : '' ?>">
            <i class="bi bi-chat-dots-fill"></i>
            <span>Messages</span>
        </a>

        <a href="<?= url('views/admin/categories.php') ?>" class="menu-item <?= $current_page === 'categories.php' ? 'active' : '' ?>">
            <i class="bi bi-tags-fill"></i>
            <span>Catégories</span>
        </a>

        <div class="menu-divider">Finances</div>

        <a href="<?= url('views/admin/subscriptions.php') ?>" class="menu-item <?= $current_page === 'subscriptions.php' ? 'active' : '' ?>">
            <i class="bi bi-award"></i>
            <span>Abonnements</span>
        </a>

        <a href="<?= url('views/admin/plans.php') ?>" class="menu-item <?= $current_page === 'plans.php' ? 'active' : '' ?>">
            <i class="bi bi-card-list"></i>
            <span>Plans</span>
        </a>

        <a href="<?= url('views/admin/payments.php') ?>" class="menu-item <?= $current_page === 'payments.php' ? 'active' : '' ?>">
            <i class="bi bi-credit-card"></i>
            <span>Paiements</span>
        </a>

        <div class="menu-divider">Système</div>

        <a href="<?= url('views/admin/statistics.php') ?>" class="menu-item <?= $current_page === 'statistics.php' ? 'active' : '' ?>">
            <i class="bi bi-graph-up"></i>
            <span>Statistiques</span>
        </a>

        <a href="<?= url('views/admin/settings.php') ?>" class="menu-item <?= $current_page === 'settings.php' ? 'active' : '' ?>">
            <i class="bi bi-gear-fill"></i>
            <span>Paramètres</span>
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="user-info">
            <img src="<?= $_SESSION['photo_profil'] ?? url('assets/images/default-avatar.png') ?>" alt="Avatar" class="user-avatar">
            <div class="user-details">
                <div class="user-name"><?= htmlspecialchars($_SESSION['prenom']) ?></div>
                <div class="user-role">Administrateur</div>
            </div>
        </div>
        <a href="<?= url('logout.php') ?>" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Déconnexion</span>
        </a>
    </div>
</div>

<!-- Topbar Mobile -->
<div class="admin-topbar d-lg-none">
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>
    <div class="topbar-brand">
        <i class="bi bi-shield-fill-check text-primary"></i>
        <span>ADMIN</span>
    </div>
    <a href="<?= url('') ?>" target="_blank" class="btn-view-site">
        <i class="bi bi-box-arrow-up-right"></i>
    </a>
</div>

<!-- Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<style>
:root {
    --sidebar-width: 260px;
    --topbar-height: 60px;
}

/* Sidebar */
.admin-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: linear-gradient(180deg, #000033 0%, #001a4d 100%);
    color: white;
    display: flex;
    flex-direction: column;
    z-index: 1000;
    transition: transform 0.3s ease;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.sidebar-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
    text-decoration: none;
    font-size: 1.5rem;
    font-weight: 700;
}

.sidebar-brand i {
    color: #0099FF;
    font-size: 2rem;
}

.brand-text {
    color: #0099FF;
}

.sidebar-toggle {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    display: none;
}

.sidebar-menu {
    flex: 1;
    overflow-y: auto;
    padding: 1rem 0;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
}

.menu-item i {
    font-size: 1.25rem;
    width: 24px;
}

.menu-item span {
    flex: 1;
}

.menu-item .badge {
    font-size: 0.7rem;
    padding: 0.25em 0.5em;
}

.menu-item:hover {
    background: rgba(0, 153, 255, 0.1);
    color: #0099FF;
}

.menu-item.active {
    background: rgba(0, 153, 255, 0.2);
    color: #0099FF;
    font-weight: 600;
}

.menu-item.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #0099FF;
}

.menu-divider {
    padding: 1rem 1.5rem 0.5rem;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: rgba(255, 255, 255, 0.5);
    font-weight: 600;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    margin-bottom: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #0099FF;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    font-size: 0.9rem;
}

.user-role {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
}

.btn-logout {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: rgba(220, 53, 69, 0.2);
    color: #ff6b6b;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    justify-content: center;
}

.btn-logout:hover {
    background: rgba(220, 53, 69, 0.3);
    color: #ff5252;
}

/* Topbar Mobile */
.admin-topbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: var(--topbar-height);
    background: linear-gradient(135deg, #000033 0%, #001a4d 100%);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1rem;
    z-index: 999;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.topbar-brand {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    font-weight: 700;
    font-size: 1.25rem;
}

.btn-view-site {
    color: white;
    font-size: 1.25rem;
    text-decoration: none;
}

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

/* Responsive */
@media (max-width: 991.98px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }

    .admin-sidebar.show {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: block;
    }

    .sidebar-overlay.show {
        display: block;
    }

    body.sidebar-open {
        overflow: hidden;
    }
}

@media (min-width: 992px) {
    .admin-topbar {
        display: none;
    }
}
</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const body = document.body;
    
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
    body.classList.toggle('sidebar-open');
}
</script>
