<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

require_once __DIR__ . '/../../models/Admin.php';
$adminModel = new Admin();
$stats = $adminModel->getDashboardStats();
$recentUsers = $adminModel->getRecentUsers(5);
$recentPayments = $adminModel->getRecentPayments(5);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body style="background:#f8f9fa;margin:0;padding:0;">
    <?php include __DIR__ . '/../../includes/navbar-admin.php'; ?>
    
    <div class="container-fluid mt-4" style="display:none;">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Administration</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link active">
                <i class="icon">📊</i> Dashboard
            </a>
            <a href="categories/index.php" class="nav-link">
                <i class="icon">📁</i> Catégories
            </a>
            <a href="users.php" class="nav-link">
                <i class="icon">👥</i> Utilisateurs
            </a>
            <a href="subscriptions-unified.php" class="nav-link">
                <i class="icon">💳</i> Abonnements
                <?php
                require_once '../../models/Subscription.php';
                $subModel = new Subscription($database);
                $pendingCount = $subModel->getPendingCount();
                if ($pendingCount > 0): ?>
                    <span style="background:#dc3545;color:white;padding:2px 8px;border-radius:10px;margin-left:auto;font-size:12px;font-weight:bold;"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="../logout.php" class="nav-link text-danger">
                <i class="icon">🚪</i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <div class="content-header">
            <h1>Dashboard</h1>
            <p class="text-muted">Vue d'ensemble de la plateforme</p>
        </div>

        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card" data-aos="fade-up">
                    <div class="stat-icon bg-primary">👥</div>
                    <div class="stat-content">
                        <h3 id="total-users">-</h3>
                        <p>Utilisateurs Total</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-icon bg-success">💼</div>
                    <div class="stat-content">
                        <h3 id="active-prestataires">-</h3>
                        <p>Prestataires Actifs</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-icon bg-info">📄</div>
                    <div class="stat-content">
                        <h3 id="active-cvs">-</h3>
                        <p>CVs Actifs</p>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-icon bg-warning">💳</div>
                    <div class="stat-content">
                        <h3 id="active-subscriptions">-</h3>
                        <p>Abonnements Actifs</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="admin-card" data-aos="fade-up">
                    <div class="card-header">
                        <h5>Inscriptions par mois</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="registrationsChart" height="300"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="admin-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-header">
                        <h5>Répartition des utilisateurs</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="usersChart" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="admin-card" data-aos="fade-up">
                    <div class="card-header">
                        <h5>Derniers utilisateurs inscrits</h5>
                    </div>
                    <div class="card-body">
                        <div class="recent-users" id="recent-users">
                            <div class="loading-spinner mx-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="admin-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="card-header">
                        <h5>Activité récente</h5>
                    </div>
                    <div class="card-body">
                        <div class="recent-activity" id="recent-activity">
                            <div class="loading-spinner mx-auto"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });

        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('../../api/admin-stats.php');
                const data = await response.json();
                
                // Update stats
                document.getElementById('total-users').textContent = data.total_users || 0;
                document.getElementById('active-prestataires').textContent = data.active_prestataires || 0;
                document.getElementById('active-cvs').textContent = data.active_cvs || 0;
                document.getElementById('active-subscriptions').textContent = data.active_subscriptions || 0;
                
                // Load charts
                loadRegistrationsChart(data.registrations_chart);
                loadUsersChart(data.users_distribution);
                
            } catch (error) {
                console.error('Erreur chargement données:', error);
                // Fallback values
                document.getElementById('total-users').textContent = '0';
                document.getElementById('active-prestataires').textContent = '0';
                document.getElementById('active-cvs').textContent = '0';
                document.getElementById('active-subscriptions').textContent = '0';
            }
        }

        function loadRegistrationsChart(data) {
            const ctx = document.getElementById('registrationsChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data?.labels || ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
                    datasets: [{
                        label: 'Inscriptions',
                        data: data?.values || [12, 19, 3, 5, 2, 3],
                        borderColor: '#0099FF',
                        backgroundColor: 'rgba(0, 153, 255, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        function loadUsersChart(data) {
            const ctx = document.getElementById('usersChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Prestataires', 'Candidats', 'Clients'],
                    datasets: [{
                        data: data?.values || [45, 30, 25],
                        backgroundColor: ['#0099FF', '#28A745', '#FFC107']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadDashboardData);
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
        
        .content-header {
            margin-bottom: 2rem;
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
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
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
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: var(--primary-dark);
        }
        
        .stat-content p {
            color: var(--medium-gray);
            margin-bottom: 0;
        }
        
        .admin-card {
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
        
        .card-body {
            padding: 1.5rem;
        }
    </style>
</body>
</html>
