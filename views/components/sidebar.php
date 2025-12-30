<?php
$sidebarType = $sidebarType ?? 'default';
$currentPage = $_SERVER['REQUEST_URI'] ?? '';

// Configuration des sidebars par type d'utilisateur
$sidebarConfigs = [
    'admin' => [
        'title' => 'ADMINISTRATION',
        'items' => [
            ['icon' => '📊', 'label' => 'Dashboard', 'url' => '/admin/dashboard', 'active' => strpos($currentPage, '/admin/dashboard') !== false],
            ['icon' => '👥', 'label' => 'Utilisateurs', 'url' => '/admin/users', 'active' => strpos($currentPage, '/admin/users') !== false],
            ['icon' => '📂', 'label' => 'Catégories', 'url' => '/admin/categories', 'active' => strpos($currentPage, '/admin/categories') !== false],
            ['icon' => '💳', 'label' => 'Abonnements', 'url' => '/admin/subscriptions', 'active' => strpos($currentPage, '/admin/subscriptions') !== false],
            ['icon' => '📈', 'label' => 'Statistiques', 'url' => '/admin/stats', 'active' => strpos($currentPage, '/admin/stats') !== false],
        ]
    ],
    'client' => [
        'title' => 'CLIENT',
        'items' => [
            ['icon' => '📊', 'label' => 'Dashboard', 'url' => '/client/dashboard', 'active' => strpos($currentPage, '/client/dashboard') !== false],
            ['icon' => '📋', 'label' => 'Mes Demandes', 'url' => '/client/demandes', 'active' => strpos($currentPage, '/client/demandes') !== false],
            ['icon' => '💬', 'label' => 'Messages', 'url' => '/client/messages', 'active' => strpos($currentPage, '/client/messages') !== false],
            ['icon' => '❤️', 'label' => 'Favoris', 'url' => '/client/favoris', 'active' => strpos($currentPage, '/client/favoris') !== false],
            ['icon' => '⭐', 'label' => 'Mes Avis', 'url' => '/client/avis', 'active' => strpos($currentPage, '/client/avis') !== false],
        ]
    ],
    'prestataire' => [
        'title' => 'PRESTATAIRE',
        'items' => [
            ['icon' => '📊', 'label' => 'Dashboard', 'url' => '/prestataire/dashboard', 'active' => strpos($currentPage, '/prestataire/dashboard') !== false],
            ['icon' => '✏️', 'label' => 'Mon Profil', 'url' => '/prestataire/profile/edit', 'active' => strpos($currentPage, '/prestataire/profile') !== false],
            ['icon' => '💬', 'label' => 'Messages', 'url' => '/prestataire/messages', 'active' => strpos($currentPage, '/prestataire/messages') !== false],
            ['icon' => '💳', 'label' => 'Abonnement', 'url' => '/prestataire/subscription', 'active' => strpos($currentPage, '/prestataire/subscription') !== false],
            ['icon' => '⭐', 'label' => 'Avis reçus', 'url' => '/prestataire/avis', 'active' => strpos($currentPage, '/prestataire/avis') !== false],
        ]
    ],
    'candidat' => [
        'title' => 'CANDIDAT',
        'items' => [
            ['icon' => '📊', 'label' => 'Dashboard', 'url' => '/lulu/views/candidat/dashboard', 'active' => strpos($currentPage, '/candidat/dashboard') !== false],
            ['icon' => '📄', 'label' => 'Mon CV', 'url' => '/lulu/views/candidat/profile/edit', 'active' => strpos($currentPage, '/candidat/profile') !== false],
            ['icon' => '💼', 'label' => 'Candidatures', 'url' => '/lulu/views/candidat/candidatures', 'active' => strpos($currentPage, '/candidat/candidatures') !== false],
            ['icon' => '🔍', 'label' => 'Recherche emploi', 'url' => '/lulu/views/candidat/search-jobs', 'active' => strpos($currentPage, '/candidat/search') !== false],
            ['icon' => '💬', 'label' => 'Messages', 'url' => '/lulu/views/candidat/messages', 'active' => strpos($currentPage, '/candidat/messages') !== false],
            ['icon' => '💳', 'label' => 'Abonnement', 'url' => '/lulu/views/candidat/subscription', 'active' => strpos($currentPage, '/candidat/subscription') !== false],
        ]
    ],
    'dual' => [
        'title' => 'PROFIL DUAL',
        'items' => [
            ['icon' => '📊', 'label' => 'Dashboard', 'url' => '/dual/dashboard', 'active' => strpos($currentPage, '/dual/dashboard') !== false],
            ['icon' => '🔄', 'label' => 'Mode Prestataire', 'url' => '/dual/switch/prestataire', 'active' => false, 'class' => 'switch-mode'],
            ['icon' => '🔄', 'label' => 'Mode Candidat', 'url' => '/dual/switch/candidat', 'active' => false, 'class' => 'switch-mode'],
            ['icon' => '📈', 'label' => 'Analyses', 'url' => '/dual/analytics', 'active' => strpos($currentPage, '/dual/analytics') !== false],
            ['icon' => '⚙️', 'label' => 'Paramètres', 'url' => '/dual/settings', 'active' => strpos($currentPage, '/dual/settings') !== false],
        ]
    ]
];

$config = $sidebarConfigs[$sidebarType] ?? $sidebarConfigs['default'];
?>

<div class="sidebar bg-gradient-primary">
    <div class="sidebar-header">
        <h4 class="text-white mb-1">
            <span class="text-light">LULU</span><span class="text-warning">-OPEN</span>
        </h4>
        <p class="text-light opacity-75 mb-0 small"><?= $config['title'] ?></p>
    </div>
    
    <nav class="sidebar-nav">
        <?php foreach ($config['items'] as $item): ?>
            <a href="<?= $item['url'] ?>" 
               class="nav-link <?= $item['active'] ? 'active' : '' ?> <?= $item['class'] ?? '' ?>"
               <?= isset($item['class']) && $item['class'] === 'switch-mode' ? 'data-switch="true"' : '' ?>>
                <span class="nav-icon"><?= $item['icon'] ?></span>
                <span class="nav-label"><?= $item['label'] ?></span>
                <?php if (isset($item['badge'])): ?>
                    <span class="badge bg-danger ms-auto"><?= $item['badge'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
        
        <div class="sidebar-divider"></div>
        
        <!-- Actions communes -->
        <a href="/" class="nav-link">
            <span class="nav-icon">🏠</span>
            <span class="nav-label">Accueil</span>
        </a>
        
        <a href="/logout" class="nav-link text-danger">
            <span class="nav-icon">🚪</span>
            <span class="nav-label">Déconnexion</span>
        </a>
    </nav>
    
    <?php if ($sidebarType === 'prestataire' || $sidebarType === 'candidat' || $sidebarType === 'dual'): ?>
        <!-- Statut abonnement -->
        <div class="sidebar-footer">
            <div class="subscription-status" id="sidebarSubscriptionStatus">
                <div class="loading-spinner"></div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.sidebar {
    width: 250px;
    min-height: 100vh;
    background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    overflow-y: auto;
}

.sidebar-header {
    padding: 2rem 1.5rem 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-nav {
    padding: 1rem 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.nav-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border-left-color: #FFC107;
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    border-left-color: #FFC107;
}

.nav-link.switch-mode {
    background: rgba(255, 193, 7, 0.1);
    border-left-color: #FFC107;
}

.nav-link.switch-mode:hover {
    background: rgba(255, 193, 7, 0.2);
}

.nav-icon {
    margin-right: 0.75rem;
    font-size: 1.1rem;
    width: 20px;
    text-align: center;
}

.nav-label {
    flex: 1;
    font-size: 0.9rem;
    font-weight: 500;
}

.sidebar-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.1);
    margin: 1rem 1.5rem;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.subscription-status {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    padding: 0.75rem;
    text-align: center;
}

.loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
}
</style>

<script>
// Charger le statut d'abonnement si applicable
document.addEventListener('DOMContentLoaded', function() {
    const statusElement = document.getElementById('sidebarSubscriptionStatus');
    if (statusElement) {
        loadSubscriptionStatus();
    }
    
    // Gestion des liens de changement de mode
    document.querySelectorAll('[data-switch="true"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (confirm('Voulez-vous changer de mode de profil ?')) {
                window.location.href = this.href;
            }
        });
    });
});

async function loadSubscriptionStatus() {
    try {
        const response = await fetch('/lulu/api/subscription-status');
        const data = await response.json();
        
        const statusElement = document.getElementById('sidebarSubscriptionStatus');
        
        if (data.active) {
            statusElement.innerHTML = `
                <div class="text-success small">
                    <div class="fw-bold">✅ Actif</div>
                    <div>${data.days_remaining} jours restants</div>
                </div>
            `;
        } else {
            statusElement.innerHTML = `
                <div class="text-warning small">
                    <div class="fw-bold">⚠️ Inactif</div>
                    <div><a href="/subscription" class="text-warning">Activer</a></div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Erreur chargement statut:', error);
        document.getElementById('sidebarSubscriptionStatus').innerHTML = '';
    }
}
</script>