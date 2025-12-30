<?php
require_once '../../config/config.php';
requireLogin();

if (!in_array($_SESSION['user_type'], ['prestataire', 'candidat'])) {
    redirect('../../index.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Prestataire - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
    <link href="../assets/css/animations.css" rel="stylesheet">
</head>
<body class="admin-page">
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Prestataire</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link active">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="profile/edit.php" class="nav-link">
                <i class="icon">✏️</i> Mon Profil
            </a>
            <a href="messages/inbox.php" class="nav-link">
                <i class="icon">💬</i> Messages
            </a>

            <a href="abonnement.php" class="nav-link">
                <i class="icon">💳</i> Abonnement
            </a>
            <a href="../../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <!-- Subscription Status Alert -->
        <?php if (isset($_SESSION['subscription_required']) && $_SESSION['subscription_required']): ?>
            <div class="alert alert-warning alert-dismissible fade show subscription-alert" data-aos="fade-down">
                <div class="d-flex align-items-center">
                    <div class="alert-icon me-3">⚠️</div>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">Abonnement requis</h6>
                        <p class="mb-2">Activez votre abonnement pour accéder à toutes les fonctionnalités.</p>
                        <a href="subscription/checkout.php" class="btn btn-warning btn-sm">Activer maintenant</a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php
        // Récupérer les informations utilisateur pour les messages d'onboarding
        global $database;
        $userInfo = $database->fetch("SELECT profil_complet FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);
        $userModel = new User($database);
        $hasPaidSubscription = $userModel->hasActivePaidSubscription($_SESSION['user_id']);
        ?>

        <?php if (($userInfo['profil_complet'] ?? 0) == 0): ?>
            <div class="alert alert-warning alert-dismissible fade show" data-aos="fade-down">
                <div class="d-flex align-items-center">
                    <div class="alert-icon me-3">⚠️</div>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">Profil incomplet</h6>
                        <p class="mb-2">Votre profil n'est pas encore complet. Complétez-le pour être actif et visible sur la plateforme.</p>
                        <a href="profile/edit.php" class="btn btn-warning btn-sm">Compléter mon profil</a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$hasPaidSubscription): ?>
            <div class="alert alert-info alert-dismissible fade show" data-aos="fade-down" data-aos-delay="100">
                <div class="d-flex align-items-center">
                    <div class="alert-icon me-3">💡</div>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">Découvrez nos abonnements Premium</h6>
                        <p class="mb-2">Vous êtes actuellement sur le plan gratuit. Pour bénéficier d'une meilleure visibilité et de fonctionnalités avancées, découvrez nos abonnements payants.</p>
                        <a href="abonnement.php" class="btn btn-info btn-sm">Voir les abonnements</a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Welcome Section -->
        <div class="welcome-section mb-4" data-aos="fade-up">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="welcome-title">Bonjour <?= explode(' ', $_SESSION['user_name'])[0] ?? 'Prestataire' ?> ! 👋</h1>
                    <p class="welcome-subtitle">Gérez votre activité et développez votre clientèle</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="subscription-status" id="subscriptionStatus">
                        <div class="status-loading">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card interactive-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-icon bg-primary animated-icon" data-icon="👁️">👁️</div>
                    <div class="stat-content">
                        <h3 id="profile-views" class="counter">-</h3>
                        <p>Vues du profil</p>
                        <small class="text-success pulse">+12% ce mois</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card interactive-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-icon bg-success animated-icon" data-icon="💬">💬</div>
                    <div class="stat-content">
                        <h3 id="messages-count" class="counter">-</h3>
                        <p>Messages reçus</p>
                        <small class="text-info">Cette semaine</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card interactive-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-icon bg-warning animated-icon" data-icon="⭐">⭐</div>
                    <div class="stat-content">
                        <h3 id="rating-average" class="counter">-</h3>
                        <p>Note moyenne</p>
                        <small class="text-muted" id="rating-count">- avis</small>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card interactive-card" data-aos="fade-up" data-aos-delay="400">
                    <div class="stat-icon bg-info animated-icon" data-icon="📊">📊</div>
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
                <div class="action-card" data-aos="fade-up">
                    <div class="card-header">
                        <h5>Actions rapides</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="profile/edit.php" class="action-btn">
                                    <div class="action-icon">✏️</div>
                                    <div class="action-content">
                                        <h6>Modifier mon profil</h6>
                                        <p>Mettez à jour vos informations</p>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-6">
                                <a href="messages/inbox.php" class="action-btn">
                                    <div class="action-icon">💬</div>
                                    <div class="action-content">
                                        <h6>Mes messages</h6>
                                        <p>Répondez à vos clients</p>
                                    </div>
                                </a>
                            </div>
                            
                            <div class="col-md-6">
                                <?php 
                                // Vérifier si l'utilisateur a déjà un CV
                                global $database;
                                $hasCv = $database->fetch("SELECT id FROM cvs WHERE utilisateur_id = ?", [$_SESSION['user_id']]);
                                ?>
                                <?php if (!$hasCv): ?>
                                    <a href="profile/add-cv.php" class="action-btn">
                                        <div class="action-icon">📄</div>
                                        <div class="action-content">
                                            <h6>Ajouter mon CV</h6>
                                            <p>Devenez aussi candidat</p>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <a href="../../candidat/dashboard.php" class="action-btn">
                                        <div class="action-icon">📄</div>
                                        <div class="action-content">
                                            <h6>Mon CV candidat</h6>
                                            <p>Gérer mon profil candidat</p>
                                        </div>
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6">
                                <a href="abonnement.php" class="action-btn">
                                    <div class="action-icon">💳</div>
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
                <div class="tips-card enhanced-tips" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-header gradient-header">
                        <h5 class="animated-title">💡 Conseils du jour</h5>
                    </div>
                    <div class="card-body">
                        <div class="tip-item enhanced-tip" data-tip="profile">
                            <div class="tip-icon">📸</div>
                            <div class="tip-content">
                                <h6>Optimisez votre profil</h6>
                                <p>Ajoutez des photos de vos réalisations pour attirer plus de clients et augmenter votre visibilité de <strong>+65%</strong>.</p>
                            </div>
                        </div>
                        
                        <div class="tip-item enhanced-tip" data-tip="response">
                            <div class="tip-icon">⚡</div>
                            <div class="tip-content">
                                <h6>Répondez rapidement</h6>
                                <p>Les clients apprécient les réponses rapides. Visez moins de <strong>2h</strong> pour maximiser vos chances.</p>
                            </div>
                        </div>
                        
                        <div class="tip-item enhanced-tip" data-tip="reviews">
                            <div class="tip-icon">🌟</div>
                            <div class="tip-content">
                                <h6>Demandez des avis</h6>
                                <p>N'hésitez pas à solliciter des avis après chaque prestation. <strong>5 avis</strong> = +40% de confiance.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row g-4">
            <div class="col-12">
                <div class="activity-card" data-aos="fade-up">
                    <div class="card-header">
                        <h5>Activité récente</h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-list" id="recentActivity">
                            <div class="loading-spinner mx-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('../../api/prestataire-stats.php');
                const data = await response.json();
                
                // Animate counters
                animateCounter('profile-views', data.profile_views || 0);
                animateCounter('messages-count', data.messages_count || 0);
                animateCounter('rating-average', data.rating_average || 0, true);
                animateCounter('profile-completion', data.profile_completion || 0, false, '%');
                
                document.getElementById('rating-count').textContent = `${data.rating_count || 0} avis`;
                
                // Update subscription status
                updateSubscriptionStatus(data.subscription);
                
                // Load recent activity
                loadRecentActivity();
                
                // Trigger icon animations
                setTimeout(() => {
                    document.querySelectorAll('.animated-icon').forEach(icon => {
                        icon.classList.add('bounce');
                    });
                }, 500);
                
            } catch (error) {
                console.error('Erreur chargement données:', error);
            }
        }
        
        // Animate counter function
        function animateCounter(elementId, targetValue, isDecimal = false, suffix = '') {
            const element = document.getElementById(elementId);
            const startValue = 0;
            const duration = 1500;
            const startTime = performance.now();
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const currentValue = startValue + (targetValue - startValue) * easeOutQuart;
                
                if (isDecimal) {
                    element.textContent = currentValue.toFixed(1) + suffix;
                } else {
                    element.textContent = Math.floor(currentValue) + suffix;
                }
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }
            
            requestAnimationFrame(updateCounter);
        }
        
        // Load recent activity
        async function loadRecentActivity() {
            try {
                const response = await fetch('../../api/prestataire-activity.php');
                const data = await response.json();
                
                const activityContainer = document.getElementById('recentActivity');
                
                if (data.activities && data.activities.length > 0) {
                    activityContainer.innerHTML = data.activities.map(activity => `
                        <div class="activity-item" data-aos="fade-up">
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
                            <div class="mb-2">📊</div>
                            <p>Aucune activité récente</p>
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
        
        // Format date helper
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

        function updateSubscriptionStatus(subscription) {
            const statusContainer = document.getElementById('subscriptionStatus');
            
            if (!subscription || !subscription.active) {
                statusContainer.innerHTML = `
                    <div class="subscription-badge bg-danger">
                        <div class="badge-icon">❌</div>
                        <div class="badge-content">
                            <div class="badge-title">Abonnement inactif</div>
                            <div class="badge-subtitle">Activez pour être visible</div>
                        </div>
                    </div>
                `;
            } else {
                const daysRemaining = subscription.days_remaining;
                const badgeClass = daysRemaining <= 7 ? 'bg-warning' : 'bg-success';
                const icon = daysRemaining <= 7 ? '⚠️' : '✅';
                
                statusContainer.innerHTML = `
                    <div class="subscription-badge ${badgeClass}">
                        <div class="badge-icon">${icon}</div>
                        <div class="badge-content">
                            <div class="badge-title">Abonnement actif</div>
                            <div class="badge-subtitle">${daysRemaining} jours restants</div>
                        </div>
                    </div>
                `;
            }
        }

        // Interactive card effects
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            
            // Add hover effects to stat cards
            document.querySelectorAll('.interactive-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    const icon = this.querySelector('.animated-icon');
                    icon.style.transform = 'scale(1.2) rotate(10deg)';
                });
                
                card.addEventListener('mouseleave', function() {
                    const icon = this.querySelector('.animated-icon');
                    icon.style.transform = 'scale(1) rotate(0deg)';
                });
            });
            
            // Enhanced tips interactions
            document.querySelectorAll('.enhanced-tip').forEach(tip => {
                tip.addEventListener('click', function() {
                    this.classList.toggle('expanded');
                });
            });
        });
    </script>

    <style>
        :root {
            --primary-color: #0099FF;
            --primary-dark: #000033;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --border-radius-lg: 12px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 8px 25px rgba(0, 153, 255, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            --font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-page {
            background: #f8f9fa;
            font-family: var(--font-family);
        }
        
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: var(--gradient-primary);
            color: white;
            padding: 2rem 0;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 0 2rem;
            margin-bottom: 2rem;
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
        
        .sidebar-nav .icon {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        
        .admin-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .welcome-section {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
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
        
        .subscription-badge {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: var(--border-radius-lg);
            color: white;
            min-width: 200px;
        }
        
        .badge-icon {
            font-size: 1.5rem;
            margin-right: 0.75rem;
        }
        
        .badge-title {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .badge-subtitle {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .stat-card:active {
            transform: translateY(-2px);
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
        
        .action-card, .tips-card, .activity-card {
            background: white;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        
        .card-header {
            background: var(--light-gray);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .card-header h5 {
            margin-bottom: 0;
            color: var(--primary-dark);
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
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .tip-item:last-child {
            border-bottom: none;
        }
        
        .tip-item h6 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }
        
        .tip-item p {
            color: var(--medium-gray);
            margin-bottom: 0;
            font-size: 0.9rem;
        }
        
        .subscription-alert {
            border-left: 4px solid #ffc107;
        }
        
        .alert-icon {
            font-size: 1.5rem;
        }
        
        /* Interactive animations */
        .interactive-card {
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .interactive-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        
        .interactive-card:hover::before {
            left: 100%;
        }
        
        .animated-icon {
            transition: all 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
            position: relative;
        }
        
        .animated-icon.bounce {
            animation: iconBounce 0.6s ease-in-out;
        }
        
        @keyframes iconBounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
        
        .counter {
            font-variant-numeric: tabular-nums;
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        /* Enhanced tips styling */
        .enhanced-tips {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        }
        
        .gradient-header {
            background: var(--gradient-primary);
            color: white;
        }
        
        .animated-title {
            animation: titleGlow 3s ease-in-out infinite alternate;
        }
        
        @keyframes titleGlow {
            from { text-shadow: 0 0 5px rgba(255,255,255,0.3); }
            to { text-shadow: 0 0 15px rgba(255,255,255,0.6); }
        }
        
        .enhanced-tip {
            display: flex;
            align-items: flex-start;
            padding: 1.2rem 0;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0.5rem 0;
        }
        
        .enhanced-tip:hover {
            background: rgba(0, 153, 255, 0.05);
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0, 153, 255, 0.1);
        }
        
        .enhanced-tip:last-child {
            border-bottom: none;
        }
        
        .tip-icon {
            font-size: 1.5rem;
            margin-right: 1rem;
            min-width: 40px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .enhanced-tip:hover .tip-icon {
            transform: scale(1.2) rotate(5deg);
        }
        
        .tip-content {
            flex: 1;
        }
        
        .tip-content h6 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .tip-content p {
            color: var(--medium-gray);
            margin-bottom: 0;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .enhanced-tip.expanded .tip-content p {
            color: var(--primary-dark);
            font-weight: 500;
        }
        
        /* Loading animations */
        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Activity styles */
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }
        
        .activity-item:hover {
            background: rgba(0, 153, 255, 0.02);
            border-radius: 8px;
            margin: 0 -1rem;
            padding: 1rem;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            font-size: 1.2rem;
            margin-right: 1rem;
            min-width: 30px;
            text-align: center;
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
    </style>
</body>
</html>