<?php
/**
 * Contrôleur des pages statiques - LULU-OPEN
 */

require_once 'BaseController.php';

class PageController extends BaseController {
    
    /**
     * Page À propos
     */
    public function about() {
        $data = [
            'title' => 'À propos - ' . APP_NAME,
            'page' => 'about'
        ];
        
        $this->render('pages/about', $data);
    }
    
    /**
     * Page Contact - Affichage formulaire
     */
    public function contact() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleContactForm();
            return;
        }
        
        $data = [
            'title' => 'Contact - ' . APP_NAME,
            'page' => 'contact'
        ];
        
        $this->render('pages/contact', $data);
    }
    
    /**
     * Traitement formulaire contact
     */
    public function handleContactForm() {
        // Vérifier CSRF
        verify_csrf_or_die();
        
        // Valider les données
        $errors = Validator::validate($_POST, [
            'nom' => ['required', ['min' => 2], ['max' => 100]],
            'email' => ['required', 'email'],
            'sujet' => ['required', ['min' => 5], ['max' => 200]],
            'message' => ['required', ['min' => 10], ['max' => 2000]]
        ]);
        
        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $_POST;
            flashMessage('Veuillez corriger les erreurs dans le formulaire.', 'error');
            redirect('/lulu/contact');
            return;
        }
        
        // Sanitizer les données
        $nom = Validator::sanitizeString($_POST['nom']);
        $email = Validator::sanitizeEmail($_POST['email']);
        $sujet = Validator::sanitizeString($_POST['sujet']);
        $message = Validator::sanitizeString($_POST['message']);
        
        // Enregistrer dans la base (optionnel)
        try {
            global $database;
            $database->insert('messages_contact', [
                'nom' => $nom,
                'email' => $email,
                'sujet' => $sujet,
                'message' => $message,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            ErrorHandler::log('Erreur enregistrement message contact: ' . $e->getMessage());
        }
        
        // TODO: Envoyer email (à implémenter selon configuration SMTP)
        
        // Message de succès
        flashMessage('Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.', 'success');
        unset($_SESSION['form_data']);
        redirect('/lulu/contact');
    }
    
    /**
     * Page CGU
     */
    public function cgu() {
        global $database;
        
        // Récupérer depuis la base
        $page = $database->fetch("SELECT * FROM pages_statiques WHERE slug = 'cgu' AND actif = 1");
        
        $data = [
            'title' => 'Conditions Générales d\'Utilisation - ' . APP_NAME,
            'page' => 'cgu',
            'content' => $page
        ];
        
        $this->render('pages/cgu', $data);
    }
    
    /**
     * Page Politique de confidentialité
     */
    public function privacy() {
        global $database;
        
        // Récupérer depuis la base
        $page = $database->fetch("SELECT * FROM pages_statiques WHERE slug = 'politique-confidentialite' AND actif = 1");
        
        $data = [
            'title' => 'Politique de Confidentialité - ' . APP_NAME,
            'page' => 'privacy',
            'content' => $page
        ];
        
        $this->render('pages/privacy', $data);
    }
    
    /**
     * Page Mentions légales
     */
    public function legal() {
        global $database;
        
        $page = $database->fetch("SELECT * FROM pages_statiques WHERE slug = 'mentions-legales' AND actif = 1");
        
        $data = [
            'title' => 'Mentions Légales - ' . APP_NAME,
            'page' => 'legal',
            'content' => $page
        ];
        
        $this->render('pages/legal', $data);
    }
}
?>
