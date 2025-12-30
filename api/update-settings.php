<?php
require_once '../config/config.php';
requireLogin();

global $database;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flashMessage('Méthode non autorisée', 'error');
    redirect('../views/prestataire_candidat/settings.php');
}

$userId = $_SESSION['user_id'];
$settingType = $_POST['setting_type'] ?? '';

try {
    if ($settingType === 'langue') {
        $langue = $_POST['langue'] ?? 'fr';
        $allowedLangues = ['fr', 'en', 'es', 'de', 'it', 'pt', 'ar'];
        
        if (!in_array($langue, $allowedLangues)) {
            throw new Exception('Langue non valide');
        }
        
        $database->query("UPDATE utilisateurs SET langue = ? WHERE id = ?", [$langue, $userId]);
        flashMessage('Langue mise à jour avec succès', 'success');
        
    } elseif ($settingType === 'devise') {
        $devise = $_POST['devise'] ?? 'EUR';
        $allowedDevises = ['EUR', 'USD', 'GBP', 'CHF', 'CAD', 'MAD', 'XOF', 'XAF'];
        
        if (!in_array($devise, $allowedDevises)) {
            throw new Exception('Devise non valide');
        }
        
        $database->query("UPDATE utilisateurs SET devise = ? WHERE id = ?", [$devise, $userId]);
        flashMessage('Devise mise à jour avec succès', 'success');
        
    } elseif ($settingType === 'theme') {
        $theme = $_POST['theme'] ?? 'light';
        $allowedThemes = ['light', 'dark'];
        
        if (!in_array($theme, $allowedThemes)) {
            throw new Exception('Thème non valide');
        }
        
        $database->query("UPDATE utilisateurs SET theme = ? WHERE id = ?", [$theme, $userId]);
        $_SESSION['theme'] = $theme;
        flashMessage('Thème mis à jour avec succès', 'success');
        
    } else {
        throw new Exception('Type de paramètre non valide');
    }
    
} catch (Exception $e) {
    flashMessage('Erreur : ' . $e->getMessage(), 'error');
}

redirect('../views/prestataire_candidat/settings.php');
