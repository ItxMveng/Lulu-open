<?php
/**
 * Helper pour la gestion des sessions et des profils utilisateur
 */

if (!function_exists('getUserProfiles')) {
    function getUserProfiles($userId) {
        global $database;
        
        $profiles = [
            'prestataire' => false,
            'candidat' => false,
            'client' => true // Par défaut, tout utilisateur peut être client
        ];
        
        // Vérifier profil prestataire
        $prestataire = $database->fetch(
            "SELECT id FROM profils_prestataires WHERE utilisateur_id = ?", 
            [$userId]
        );
        if ($prestataire) {
            $profiles['prestataire'] = true;
        }
        
        // Vérifier profil candidat
        $candidat = $database->fetch(
            "SELECT id FROM cvs WHERE utilisateur_id = ?", 
            [$userId]
        );
        if ($candidat) {
            $profiles['candidat'] = true;
        }
        
        return $profiles;
    }
}

if (!function_exists('getEffectiveUserType')) {
    function getEffectiveUserType($userId, $baseType) {
        $profiles = getUserProfiles($userId);
        
        if ($profiles['prestataire'] && $profiles['candidat']) {
            return 'prestataire_candidat';
        } elseif ($profiles['prestataire']) {
            return 'prestataire';
        } elseif ($profiles['candidat']) {
            return 'candidat';
        } else {
            return $baseType;
        }
    }
}

if (!function_exists('updateUserSession')) {
    function updateUserSession($userId) {
        global $database;
        
        $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$userId]);
        if (!$user) return false;
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['type_utilisateur'];
        $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
        
        $profiles = getUserProfiles($userId);
        $_SESSION['has_prestataire_profile'] = $profiles['prestataire'];
        $_SESSION['has_candidat_profile'] = $profiles['candidat'];
        $_SESSION['effective_user_type'] = getEffectiveUserType($userId, $user['type_utilisateur']);
        
        return true;
    }
}

if (!function_exists('canAccessDashboard')) {
    function canAccessDashboard($requiredType) {
        if (!isLoggedIn()) return false;
        
        $effectiveType = $_SESSION['effective_user_type'] ?? $_SESSION['user_type'];
        
        switch ($requiredType) {
            case 'admin':
                return $_SESSION['user_type'] === 'admin';
            case 'prestataire':
                return in_array($effectiveType, ['prestataire', 'prestataire_candidat']);
            case 'candidat':
                return in_array($effectiveType, ['candidat', 'prestataire_candidat']);
            case 'client':
                return true; // Tous peuvent être clients
            default:
                return false;
        }
    }
}

if (!function_exists('requireDashboardAccess')) {
    function requireDashboardAccess($requiredType) {
        if (!canAccessDashboard($requiredType)) {
            flashMessage('Accès non autorisé', 'error');
            header('Location: ../../login-fixed.php');
            exit;
        }
    }
}

if (!function_exists('getDashboardUrl')) {
    function getDashboardUrl($userType = null) {
        if (!$userType) {
            $userType = $_SESSION['effective_user_type'] ?? $_SESSION['user_type'] ?? 'client';
        }
        
        switch ($userType) {
            case 'admin':
                return 'views/admin/dashboard.php';
            case 'prestataire':
            case 'prestataire_candidat':
                return 'views/prestataire/dashboard.php';
            case 'candidat':
                return 'views/candidat/dashboard.php';
            default:
                return 'index.php';
        }
    }
}

if (!function_exists('getProfileSwitchOptions')) {
    function getProfileSwitchOptions() {
        if (!isLoggedIn()) return [];
        
        $options = [];
        
        if ($_SESSION['has_prestataire_profile'] ?? false) {
            $options[] = [
                'type' => 'prestataire',
                'label' => 'Prestataire',
                'url' => 'views/prestataire/dashboard.php',
                'icon' => '💼'
            ];
        }
        
        if ($_SESSION['has_candidat_profile'] ?? false) {
            $options[] = [
                'type' => 'candidat',
                'label' => 'Candidat',
                'url' => 'views/candidat/dashboard.php',
                'icon' => '📄'
            ];
        }
        
        // Toujours ajouter l'option client
        $options[] = [
            'type' => 'client',
            'label' => 'Client',
            'url' => 'index.php',
            'icon' => '👤'
        ];
        
        return $options;
    }
}

if (!function_exists('renderProfileSwitcher')) {
    function renderProfileSwitcher() {
        $options = getProfileSwitchOptions();
        if (count($options) <= 1) return '';
        
        $currentType = $_SESSION['effective_user_type'] ?? 'client';
        
        $html = '<div class="profile-switcher dropdown">';
        $html .= '<button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">';
        
        foreach ($options as $option) {
            if ($option['type'] === $currentType || 
                ($currentType === 'prestataire_candidat' && in_array($option['type'], ['prestataire', 'candidat']))) {
                $html .= $option['icon'] . ' ' . $option['label'];
                break;
            }
        }
        
        $html .= '</button>';
        $html .= '<ul class="dropdown-menu">';
        
        foreach ($options as $option) {
            $active = ($option['type'] === $currentType) ? 'active' : '';
            $html .= '<li><a class="dropdown-item ' . $active . '" href="' . $option['url'] . '">';
            $html .= $option['icon'] . ' ' . $option['label'] . '</a></li>';
        }
        
        $html .= '</ul></div>';
        
        return $html;
    }
}
?>