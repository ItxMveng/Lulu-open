<?php
// Gestionnaire global du thème et de la langue
if (!function_exists('getUserSettings')) {
    function getUserSettings() {
        if (!isset($_SESSION['user_id'])) {
            return ['theme' => 'light', 'langue' => 'fr', 'devise' => 'EUR'];
        }
        
        global $database;
        $settings = $database->fetch(
            "SELECT theme, langue, devise FROM utilisateurs WHERE id = ?",
            [$_SESSION['user_id']]
        );
        
        return [
            'theme' => $settings['theme'] ?? 'light',
            'langue' => $settings['langue'] ?? 'fr',
            'devise' => $settings['devise'] ?? 'EUR'
        ];
    }
}

if (!function_exists('applyTheme')) {
    function applyTheme() {
        $settings = getUserSettings();
        echo '<script>document.documentElement.setAttribute("data-theme", "' . $settings['theme'] . '");</script>';
        echo '<style>
            :root[data-theme="dark"] {
                --bg-primary: #1a1a1a;
                --bg-secondary: #2d2d2d;
                --bg-tertiary: #3a3a3a;
                --text-primary: #e0e0e0;
                --text-secondary: #999;
                --border-color: #4a4a4a;
            }
            
            [data-theme="dark"] body {
                background: var(--bg-primary) !important;
                color: var(--text-primary) !important;
            }
            
            [data-theme="dark"] .card,
            [data-theme="dark"] .admin-content,
            [data-theme="dark"] .welcome-section {
                background: var(--bg-secondary) !important;
                color: var(--text-primary) !important;
            }
            
            [data-theme="dark"] .form-control,
            [data-theme="dark"] .form-select {
                background: var(--bg-tertiary) !important;
                border-color: var(--border-color) !important;
                color: var(--text-primary) !important;
            }
            
            [data-theme="dark"] .text-muted {
                color: var(--text-secondary) !important;
            }
            
            [data-theme="dark"] .alert-info {
                background: #1e3a5f !important;
                border-color: #2a5a8f !important;
                color: #a0c4ff !important;
            }
            
            [data-theme="dark"] .stat-card {
                opacity: 0.95;
            }
        </style>';
    }
}
?>
