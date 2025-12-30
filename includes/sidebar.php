<?php
function renderSidebar($userType, $currentPage = '', $user = null) {
    $sidebarItems = [];
    
    switch ($userType) {
        case 'candidat':
        case 'prestataire_candidat':
            $sidebarItems = [
                ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'emoji' => '📊'],
                ['icon' => 'bi-person-circle', 'label' => 'Mon Profil', 'url' => 'profile/edit.php', 'emoji' => '👤'],
                ['icon' => 'bi-briefcase', 'label' => 'Candidatures', 'url' => 'candidatures.php', 'emoji' => '💼'],
                ['icon' => 'bi-envelope', 'label' => 'Messages', 'url' => 'messages.php', 'emoji' => '💬'],
                ['icon' => 'bi-credit-card', 'label' => 'Abonnement', 'url' => 'abonnement.php', 'emoji' => '💳'],
                ['icon' => 'bi-gear', 'label' => 'Paramètres', 'url' => 'settings.php', 'emoji' => '⚙️']
            ];
            break;
            
        case 'prestataire':
            $sidebarItems = [
                ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'emoji' => '📊'],
                ['icon' => 'bi-person-badge', 'label' => 'Mon Profil', 'url' => 'profile/edit.php', 'emoji' => '👤'],
                ['icon' => 'bi-envelope', 'label' => 'Messages', 'url' => 'messages/inbox.php', 'emoji' => '💬'],
                ['icon' => 'bi-credit-card', 'label' => 'Abonnement', 'url' => 'abonnement.php', 'emoji' => '💳'],
                ['icon' => 'bi-gear', 'label' => 'Paramètres', 'url' => 'settings.php', 'emoji' => '⚙️']
            ];
            break;
            
        case 'client':
            $sidebarItems = [
                ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'emoji' => '📊'],
                ['icon' => 'bi-search', 'label' => 'Rechercher', 'url' => '../../search.php', 'emoji' => '🔍'],
                ['icon' => 'bi-heart', 'label' => 'Favoris', 'url' => 'favoris.php', 'emoji' => '❤️'],
                ['icon' => 'bi-envelope', 'label' => 'Messages', 'url' => 'messages.php', 'emoji' => '💬'],
                ['icon' => 'bi-gear', 'label' => 'Paramètres', 'url' => 'settings.php', 'emoji' => '⚙️']
            ];
            break;
            
        case 'admin':
            $sidebarItems = [
                ['icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'url' => 'dashboard.php', 'emoji' => '📊'],
                ['icon' => 'bi-people', 'label' => 'Utilisateurs', 'url' => 'users.php', 'emoji' => '👥'],
                ['icon' => 'bi-credit-card', 'label' => 'Abonnements', 'url' => 'subscriptions.php', 'emoji' => '💳'],
                ['icon' => 'bi-envelope', 'label' => 'Messages', 'url' => 'messages.php', 'emoji' => '💬'],
                ['icon' => 'bi-gear', 'label' => 'Paramètres', 'url' => 'settings.php', 'emoji' => '⚙️']
            ];
            break;
    }
    
    $baseUrl = getCurrentBaseUrl($userType);
    ?>
    <div class="modern-sidebar">
        <div class="sidebar-header">
            <div class="brand-section">
                <h4 class="brand-title"><span class="text-primary">LULU</span>-OPEN</h4>
                <p class="brand-subtitle">Espace <?= ucfirst($userType) ?></p>
            </div>
            <div class="user-profile">
                <div class="user-avatar">
                    <?php if ($user && $user['photo_profil']): ?>
                        <img src="<?= getPhotoUrl($user['photo_profil']) ?>" class="avatar-img" alt="Photo">
                    <?php else: ?>
                        <div class="avatar-placeholder">
                            <?= strtoupper(substr($user['prenom'] ?? 'U', 0, 1) . substr($user['nom'] ?? 'S', 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h6 class="user-name"><?= htmlspecialchars(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '')) ?></h6>
                    <span class="user-role"><?= ucfirst($userType) ?></span>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-navigation">
            <?php foreach ($sidebarItems as $item): ?>
                <?php $isActive = basename($currentPage) === basename($item['url']); ?>
                <a href="<?= $baseUrl . $item['url'] ?>" class="nav-item <?= $isActive ? 'active' : '' ?>">
                    <span class="nav-emoji"><?= $item['emoji'] ?></span>
                    <span class="nav-text"><?= $item['label'] ?></span>
                    <?php if ($isActive): ?>
                        <span class="active-indicator"></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../../logout.php" class="logout-btn">
                <span class="nav-emoji">🚪</span>
                <span class="nav-text">Déconnexion</span>
            </a>
        </div>
    </div>
    
    <style>
    :root {
        --primary-color: #0099FF;
        --primary-dark: #000033;
        --sidebar-width: 280px;
        --gradient-primary: linear-gradient(135deg, #000033 0%, #0099FF 100%);
        --shadow-soft: 0 4px 20px rgba(0, 0, 0, 0.08);
        --border-radius: 12px;
        --transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
    }
    
    .modern-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--gradient-primary);
        color: white;
        display: flex;
        flex-direction: column;
        z-index: 1000;
        box-shadow: var(--shadow-soft);
    }
    
    .sidebar-header {
        padding: 2rem 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .brand-section {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    
    .brand-title {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: white;
    }
    
    .brand-subtitle {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 0;
    }
    
    .user-profile {
        display: flex;
        align-items: center;
        padding: 1rem;
        background: rgba(255, 255, 255, 0.1);
        border-radius: var(--border-radius);
        backdrop-filter: blur(10px);
    }
    
    .user-avatar {
        margin-right: 0.75rem;
    }
    
    .avatar-img {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .avatar-placeholder {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.9rem;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .user-name {
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 0.1rem;
        color: white;
    }
    
    .user-role {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.7);
    }
    
    .sidebar-navigation {
        flex: 1;
        padding: 1.5rem 1rem;
        overflow-y: auto;
    }
    
    .nav-item {
        display: flex;
        align-items: center;
        padding: 0.875rem 1rem;
        margin-bottom: 0.5rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    
    .nav-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s;
    }
    
    .nav-item:hover::before {
        left: 100%;
    }
    
    .nav-item:hover {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        transform: translateX(5px);
    }
    
    .nav-item.active {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }
    
    .nav-emoji {
        font-size: 1.2rem;
        margin-right: 0.75rem;
        min-width: 24px;
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .nav-item:hover .nav-emoji {
        transform: scale(1.2);
    }
    
    .nav-text {
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .active-indicator {
        position: absolute;
        right: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 20px;
        background: white;
        border-radius: 2px 0 0 2px;
    }
    
    .sidebar-footer {
        padding: 1rem;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .logout-btn {
        display: flex;
        align-items: center;
        padding: 0.875rem 1rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        border-radius: var(--border-radius);
        transition: var(--transition);
        background: rgba(220, 53, 69, 0.1);
        border: 1px solid rgba(220, 53, 69, 0.2);
    }
    
    .logout-btn:hover {
        background: rgba(220, 53, 69, 0.2);
        color: white;
        transform: translateX(5px);
    }
    
    .main-content {
        margin-left: var(--sidebar-width);
        min-height: 100vh;
        background: #f8f9fa;
    }
    
    @media (max-width: 768px) {
        .modern-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        .modern-sidebar.show {
            transform: translateX(0);
        }
        .main-content {
            margin-left: 0;
        }
    }
    
    /* Scrollbar styling */
    .sidebar-navigation::-webkit-scrollbar {
        width: 4px;
    }
    
    .sidebar-navigation::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-navigation::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.3);
        border-radius: 2px;
    }
    
    .sidebar-navigation::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.5);
    }
    </style>
    <?php
}

function getCurrentBaseUrl($userType) {
    $basePaths = [
        'candidat' => '/lulu/views/candidat/',
        'prestataire' => '/lulu/views/prestataire/',
        'prestataire_candidat' => '/lulu/views/candidat/',
        'client' => '/lulu/views/client/',
        'admin' => '/lulu/views/admin/'
    ];
    
    return $basePaths[$userType] ?? '/lulu/views/candidat/';
}

function getPhotoUrl($photoPath) {
    if (strpos($photoPath, 'http') === 0) {
        return $photoPath;
    }
    return '/lulu/uploads/profiles/' . $photoPath;
}
?>