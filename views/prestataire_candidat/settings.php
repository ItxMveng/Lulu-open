<?php
require_once '../../config/config.php';
require_once '../../includes/theme-handler.php';
requireLogin();

if ($_SESSION['user_type'] !== 'prestataire_candidat') {
    redirect('../../index.php');
}

global $database;
$userId = $_SESSION['user_id'];

// Récupérer les paramètres utilisateur
$settings = getUserSettings();
$settingsDb = $database->fetch("SELECT langue, devise, theme FROM utilisateurs WHERE id = ?", [$userId]);
$settings = array_merge($settings, $settingsDb);
?>
<?php applyTheme(); ?>
<!DOCTYPE html>
<html lang="<?= $settings['langue'] ?? 'fr' ?>" data-theme="<?= $settings['theme'] ?? 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <h4><span class="text-primary">LULU</span>-OPEN</h4>
            <p class="text-muted">Espace Dual</p>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="bi bi-grid"></i> Dashboard
            </a>
            <a href="messages/inbox.php" class="nav-link">
                <i class="bi bi-chat-dots"></i> Messages
            </a>
            <a href="abonnement.php" class="nav-link">
                <i class="bi bi-credit-card"></i> Abonnement
            </a>
            <a href="settings.php" class="nav-link active">
                <i class="bi bi-gear"></i> Paramètres
            </a>
            <a href="../../logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="admin-content">
        <?php if ($flashMessage = getFlashMessage()): ?>
            <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flashMessage['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="welcome-section mb-4">
            <h1><i class="bi bi-gear"></i> Paramètres</h1>
            <p>Personnalisez votre expérience sur LULU-OPEN</p>
        </div>

        <div class="row g-4">
            <!-- Langue -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-translate"></i> Langue</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../api/update-settings.php">
                            <input type="hidden" name="setting_type" value="langue">
                            <div class="mb-3">
                                <label class="form-label">Choisissez votre langue</label>
                                <select class="form-select" name="langue" required>
                                    <option value="fr" <?= ($settings['langue'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>🇫🇷 Français</option>
                                    <option value="en" <?= ($settings['langue'] ?? 'fr') === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                                    <option value="es" <?= ($settings['langue'] ?? 'fr') === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
                                    <option value="de" <?= ($settings['langue'] ?? 'fr') === 'de' ? 'selected' : '' ?>>🇩🇪 Deutsch</option>
                                    <option value="it" <?= ($settings['langue'] ?? 'fr') === 'it' ? 'selected' : '' ?>>🇮🇹 Italiano</option>
                                    <option value="pt" <?= ($settings['langue'] ?? 'fr') === 'pt' ? 'selected' : '' ?>>🇵🇹 Português</option>
                                    <option value="ar" <?= ($settings['langue'] ?? 'fr') === 'ar' ? 'selected' : '' ?>>🇸🇦 العربية</option>
                                </select>
                                <small class="text-muted">La langue par défaut est détectée automatiquement selon votre navigateur</small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Devise -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-currency-exchange"></i> Devise</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../api/update-settings.php">
                            <input type="hidden" name="setting_type" value="devise">
                            <div class="mb-3">
                                <label class="form-label">Choisissez votre devise</label>
                                <select class="form-select" name="devise" required>
                                    <option value="EUR" <?= ($settings['devise'] ?? 'EUR') === 'EUR' ? 'selected' : '' ?>>€ Euro (EUR)</option>
                                    <option value="USD" <?= ($settings['devise'] ?? 'EUR') === 'USD' ? 'selected' : '' ?>>$ Dollar US (USD)</option>
                                    <option value="GBP" <?= ($settings['devise'] ?? 'EUR') === 'GBP' ? 'selected' : '' ?>>£ Livre Sterling (GBP)</option>
                                    <option value="CHF" <?= ($settings['devise'] ?? 'EUR') === 'CHF' ? 'selected' : '' ?>>CHF Franc Suisse (CHF)</option>
                                    <option value="CAD" <?= ($settings['devise'] ?? 'EUR') === 'CAD' ? 'selected' : '' ?>>$ Dollar Canadien (CAD)</option>
                                    <option value="MAD" <?= ($settings['devise'] ?? 'EUR') === 'MAD' ? 'selected' : '' ?>>DH Dirham Marocain (MAD)</option>
                                    <option value="XOF" <?= ($settings['devise'] ?? 'EUR') === 'XOF' ? 'selected' : '' ?>>CFA Franc CFA (XOF)</option>
                                    <option value="XAF" <?= ($settings['devise'] ?? 'EUR') === 'XAF' ? 'selected' : '' ?>>FCFA Franc CFA Central (XAF)</option>
                                </select>
                                <small class="text-muted">La devise par défaut est détectée selon votre pays</small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Thème -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-moon-stars"></i> Thème</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="../../api/update-settings.php">
                            <input type="hidden" name="setting_type" value="theme">
                            <div class="mb-3">
                                <label class="form-label">Choisissez votre thème</label>
                                <select class="form-select" name="theme" required onchange="previewTheme(this.value)">
                                    <option value="light" <?= ($settings['theme'] ?? 'light') === 'light' ? 'selected' : '' ?>>☀️ Clair</option>
                                    <option value="dark" <?= ($settings['theme'] ?? 'light') === 'dark' ? 'selected' : '' ?>>🌙 Sombre</option>
                                </select>
                                <small class="text-muted">Le thème s'applique à toute la plateforme</small>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Enregistrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Aperçu salutation -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-clock"></i> Salutation dynamique</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h4 id="dynamicGreeting" class="mb-2"></h4>
                            <p class="mb-0">La salutation change automatiquement selon l'heure de la journée :</p>
                            <ul class="mb-0 mt-2">
                                <li><strong>5h - 12h :</strong> Bonjour / Good morning / Buenos días</li>
                                <li><strong>12h - 18h :</strong> Bon après-midi / Good afternoon / Buenas tardes</li>
                                <li><strong>18h - 5h :</strong> Bonsoir / Good evening / Buenas noches</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateGreeting() {
            const hour = new Date().getHours();
            const langue = '<?= $settings['langue'] ?? 'fr' ?>';
            const userName = '<?= explode(' ', $_SESSION['user_name'])[0] ?>';
            
            const greetings = {
                fr: {
                    morning: 'Bonjour',
                    afternoon: 'Bon après-midi',
                    evening: 'Bonsoir'
                },
                en: {
                    morning: 'Good morning',
                    afternoon: 'Good afternoon',
                    evening: 'Good evening'
                },
                es: {
                    morning: 'Buenos días',
                    afternoon: 'Buenas tardes',
                    evening: 'Buenas noches'
                },
                de: {
                    morning: 'Guten Morgen',
                    afternoon: 'Guten Tag',
                    evening: 'Guten Abend'
                },
                it: {
                    morning: 'Buongiorno',
                    afternoon: 'Buon pomeriggio',
                    evening: 'Buonasera'
                },
                pt: {
                    morning: 'Bom dia',
                    afternoon: 'Boa tarde',
                    evening: 'Boa noite'
                },
                ar: {
                    morning: 'صباح الخير',
                    afternoon: 'مساء الخير',
                    evening: 'مساء الخير'
                }
            };
            
            let period = 'morning';
            if (hour >= 12 && hour < 18) period = 'afternoon';
            else if (hour >= 18 || hour < 5) period = 'evening';
            
            const greeting = greetings[langue]?.[period] || greetings.fr[period];
            document.getElementById('dynamicGreeting').textContent = `${greeting} ${userName} ! 👋`;
        }
        
        updateGreeting();
        setInterval(updateGreeting, 60000); // Mise à jour chaque minute
        
        function previewTheme(theme) {
            if (theme === 'dark') {
                document.body.classList.add('dark-theme');
            } else {
                document.body.classList.remove('dark-theme');
            }
        }
        
        // Appliquer le thème au chargement
        const currentTheme = '<?= $settings['theme'] ?? 'light' ?>';
        if (currentTheme === 'dark') {
            document.body.classList.add('dark-theme');
        }
    </script>

    <style>
        body { background: #f8f9fa; }
        .bg-gradient-primary {
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.75rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0099FF;
            box-shadow: 0 0 0 0.2rem rgba(0, 153, 255, 0.25);
        }
        .btn {
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #000033, #0099FF);
            color: white;
            padding: 2rem 0;
        }
        .sidebar-header { padding: 0 2rem; margin-bottom: 2rem; }
        .sidebar-nav .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
        }
        .sidebar-nav .nav-link:hover, .sidebar-nav .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .admin-content {
            margin-left: 250px;
            padding: 2rem;
            background: #f8f9fa;
            min-height: 100vh;
        }
        .welcome-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Dark Theme */
        body.dark-theme {
            background: #1a1a1a;
            color: #e0e0e0;
        }
        body.dark-theme .admin-content {
            background: #1a1a1a;
        }
        body.dark-theme .card {
            background: #2d2d2d;
            color: #e0e0e0;
        }
        body.dark-theme .welcome-section {
            background: #2d2d2d;
        }
        body.dark-theme .form-control,
        body.dark-theme .form-select {
            background: #3a3a3a;
            border-color: #4a4a4a;
            color: #e0e0e0;
        }
        body.dark-theme .form-control:focus,
        body.dark-theme .form-select:focus {
            background: #3a3a3a;
            border-color: #0099FF;
            color: #e0e0e0;
        }
        body.dark-theme .text-muted {
            color: #999 !important;
        }
        body.dark-theme .alert-info {
            background: #1e3a5f;
            border-color: #2a5a8f;
            color: #a0c4ff;
        }
    </style>
</body>
</html>
