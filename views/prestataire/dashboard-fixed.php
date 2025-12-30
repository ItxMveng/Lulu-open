<?php
require_once '../../config/config.php';
require_once '../../includes/session-helper.php';

requireLogin();
requireDashboardAccess('prestataire');

// Mise à jour de la session si nécessaire
updateUserSession($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Prestataire - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
</head>
<body class="admin-page">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Prestataire</p>
            
            <!-- Profile Switcher -->
            <?= renderProfileSwitcher() ?>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard-fixed.php" class="nav-link active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a href="profile/edit.php" class="nav-link">
                <i class="bi bi-person-gear"></i> Mon Profil
            </a>
            <a href="messages/inbox.php" class="nav-link">
                <i class="bi bi-chat-dots"></i> Messages
                <span class="badge bg-danger ms-auto" id="unread-messages">0</span>
            </a>
            
            <?php if ($_SESSION['has_candidat_profile']): ?>
                <a href="../candidat/dashboard.php" class="nav-link">
                    <i class="bi bi-file-person"></i> Mon CV Candidat
                </a>
            <?php else: ?>
                <a href="profile/add-cv.php" class="nav-link">
                    <i class="bi bi-plus-circle"></i> Ajouter un CV
                </a>
            <?php endif; ?>
            
            <a href="subscription/manage.php" class="nav-link">
                <i class="bi bi-credit-card"></i> Abonnement
            </a>
            
            <hr class="sidebar-divider">
            
            <a href="../../logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <!-- Flash Messages -->
        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-section mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="welcome-title">
                        Bonjour <?= explode(' ', $_SESSION['user_name'])[0] ?? 'Prestataire' ?> ! 👋
                    </h1>
                    <p class="welcome-subtitle">
                        <?php if ($_SESSION['effective_user_type'] === 'prestataire_candidat'): ?>
                            Gérez votre activité de prestataire et votre recherche d'emploi
                        <?php else: ?>
                            Gérez votre activité et développez votre clientèle
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="user-info">
                        <div class="user-type-badge">
                            <?php if ($_SESSION['effective_user_type'] === 'prestataire_candidat'): ?>
                                <span class="badge bg-primary">💼 Prestataire</span>
                                <span class="badge bg-success">📄 Candidat</span>
                            <?php else: ?>
                                <span class="badge bg-primary">💼 Prestataire</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-primary">
                        <i class="bi bi-eye"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="profile-views" class="counter">-</h3>
                        <p>Vues du profil</p>
                        <small class="text-success">+12% ce mois</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-success">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="messages-count" class="counter">-</h3>
                        <p>Messages reçus</p>
                        <small class="text-info">Cette semaine</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-warning">
                        <i class="bi bi-star-fill"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="rating-average" class="counter">-</h3>
                        <p>Note moyenne</p>
                        <small class="text-muted" id="rating-count">- avis</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-info">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="profile-completion" class="counter">-</h3>
                        <p>Profil complété</p>
                        <small class="text-primary">Optimisez votre visibilité</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="profile/edit.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-person-gear"></i>
                                    </div>
                                    <div class="action-content">
                                        <h6>Modifier mon profil</h6>
                                        <p>Mettez à jour vos informations</p>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-6">
                                <a href="messages/inbox.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-chat-dots"></i>
                                    </div>
                                    <div class="action-content">
                                        <h6>Mes messages</h6>
                                        <p>Répondez à vos clients</p>
                                    </div>
                                </a>
                            </div>
                            
                            <?php if (!$_SESSION['has_candidat_profile']): ?>
                                <div class="col-md-6">
                                    <a href="profile/add-cv.php" class="action-btn">
                                        <div class="action-icon">
                                            <i class="bi bi-plus-circle"></i>
                                        </div>
                                        <div class="action-content">
                                            <h6>Ajouter mon CV</h6>
                                            <p>Devenez aussi candidat</p>
                                        </div>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="col-md-6">
                                    <a href="../candidat/dashboard.php" class="action-btn">
                                        <div class="action-icon">
                                            <i class="bi bi-file-person"></i>
                                        </div>
                                        <div class="action-content">
                                            <h6>Mon CV candidat</h6>
                                            <p>Gérer mon profil candidat</p>
                                        </div>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="col-md-6">
                                <a href="subscription/manage.php" class="action-btn">
                                    <div class="action-icon">
                                        <i class="bi bi-credit-card"></i>
                                    </div>
                                    <div class="action-content">
                                        <h6>Gérer l'abonnement</h6>
                                        <p>Paramètres de facturation</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">💡 Conseils du jour</h5>
                    </div>
                    <div class="card-body">
                        <div class="tip-item">
                            <div class="tip-icon">📸</div>
                            <div class="tip-content">
                                <h6>Optimisez votre profil</h6>
                                <p>Ajoutez des photos de vos réalisations pour attirer plus de clients.</p>
                            </div>
                        </div>
                        
                        <div class="tip-item">
                            <div class="tip-icon">⚡</div>
                            <div class="tip-content">
                                <h6>Répondez rapidement</h6>
                                <p>Les clients apprécient les réponses rapides. Visez moins de 2h.</p>
                            </div>
                        </div>
                        
                        <?php if ($_SESSION['has_candidat_profile']): ?>
                            <div class="tip-item">
                                <div class="tip-icon">🎯</div>
                                <div class="tip-content">
                                    <h6>Double opportunité</h6>
                                    <p>Votre profil prestataire + candidat vous donne plus de visibilité !</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Activité récente</h5>
                    </div>
                    <div class="card-body">
                        <div id="recentActivity">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('../../api/prestataire-stats.php');
                const data = await response.json();
                
                // Update counters
                document.getElementById('profile-views').textContent = data.profile_views || 0;
                document.getElementById('messages-count').textContent = data.messages_count || 0;
                document.getElementById('rating-average').textContent = (data.rating_average || 0).toFixed(1);
                document.getElementById('profile-completion').textContent = (data.profile_completion || 0) + '%';
                document.getElementById('rating-count').textContent = `${data.rating_count || 0} avis`;
                
                // Update unread messages badge
                const unreadBadge = document.getElementById('unread-messages');
                if (data.unread_messages > 0) {
                    unreadBadge.textContent = data.unread_messages;
                    unreadBadge.style.display = 'inline';
                } else {
                    unreadBadge.style.display = 'none';
                }
                
                loadRecentActivity();
                
            } catch (error) {
                console.error('Erreur chargement données:', error);
            }
        }
        
        // Load recent activity
        async function loadRecentActivity() {
            try {
                const response = await fetch('../../api/prestataire-activity.php');
                const data = await response.json();
                
                const activityContainer = document.getElementById('recentActivity');
                
                if (data.activities && data.activities.length > 0) {
                    activityContainer.innerHTML = data.activities.map(activity => `
                        <div class="activity-item">
                            <div class="activity-icon">${activity.icon}</div>
                            <div class="activity-content">
                                <p class="activity-description">${activity.description}</p>
                                <small class="activity-time">${formatDate(activity.created_at)}</small>
                            </div>
                        </div>
                    `).join('');
                } else {
                    activityContainer.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
                            <p class="mt-2">Aucune activité récente</p>
                        </div>
                    `;
                }
                
            } catch (error) {
                console.error('Erreur chargement activité:', error);
                document.getElementById('recentActivity').innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <p>Erreur de chargement</p>
                    </div>
                `;
            }
        }
        
        function formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffTime = Math.abs(now - date);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            
            if (diffDays === 1) {
                return 'Hier';
            } else if (diffDays < 7) {
                return `Il y a ${diffDays} jours`;
            } else {
                return date.toLocaleDateString('fr-FR');
            }
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            
            // Refresh data every 5 minutes
            setInterval(loadDashboardData, 300000);
        });
    </script>

    <style>
        :root {
            --primary-color: #0099FF;
            --primary-dark: #000033;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --font-family: 'Inter', sans-serif;
        }
        
        body {
            font-family: var(--font-family);
            background: var(--light-gray);
        }
        
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            color: white;
            padding: 2rem 0;
            z-index: 1000;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 2rem;
            margin-bottom: 2rem;
        }
        
        .sidebar-header h4 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .profile-switcher {
            margin-top: 1rem;
        }
        
        .profile-switcher .btn {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 0.9rem;
        }
        
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem 2rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-nav .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
        }
        
        .sidebar-divider {
            border-color: rgba(255, 255, 255, 0.2);
            margin: 1rem 2rem;
        }
        
        .admin-content {
            margin-left: 280px;
            padding: 2rem;
        }
        
        .welcome-section {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }
        
        .welcome-subtitle {
            color: var(--medium-gray);
            margin-bottom: 0;
        }
        
        .user-type-badge .badge {
            margin-left: 0.5rem;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 153, 255, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
            color: white;
        }
        
        .stat-content h3 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--primary-dark);
        }
        
        .stat-content p {
            color: var(--medium-gray);
            margin-bottom: 0.25rem;
        }
        
        .action-btn {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #E9ECEF;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
        }
        
        .action-btn:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            color: inherit;
        }
        
        .action-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: var(--primary-color);
        }
        
        .action-content h6 {
            margin-bottom: 0.25rem;
            color: var(--primary-dark);
        }
        
        .action-content p {
            margin-bottom: 0;
            color: var(--medium-gray);
            font-size: 0.9rem;
        }
        
        .tip-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .tip-item:last-child {
            border-bottom: none;
        }
        
        .tip-icon {
            font-size: 1.2rem;
            margin-right: 1rem;
            min-width: 30px;
        }
        
        .tip-content h6 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .tip-content p {
            color: var(--medium-gray);
            margin-bottom: 0;
            font-size: 0.8rem;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            font-size: 1.2rem;
            margin-right: 1rem;
            min-width: 30px;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-description {
            margin-bottom: 0.25rem;
            color: var(--primary-dark);
            font-size: 0.9rem;
        }
        
        .activity-time {
            color: var(--medium-gray);
            font-size: 0.8rem;
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            
            .admin-content {
                margin-left: 0;
            }
        }
    </style>
</body>
</html>