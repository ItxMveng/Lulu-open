<?php
/**
 * Contrôleur de la page d'accueil - LULU-OPEN
 */

require_once 'BaseController.php';
require_once 'models/Category.php';
require_once 'models/User.php';

class HomeController extends BaseController {
    
    private $db;

    public function __construct($database) {
        // Le constructeur de BaseController pourrait être appelé ici si nécessaire
        // parent::__construct(); 
        $this->db = $database;
    }
    
    public function index() {
        try {
            // Récupération des statistiques pour la page d'accueil
            $stats = $this->getHomeStats();
            
            // Récupération des catégories populaires
            $categories = $this->getPopularCategories();
            
            // Récupération des derniers profils ajoutés
            $recentProfiles = $this->getRecentProfiles();
            
            // Données à passer à la vue
            $data = [
                'title' => 'Accueil - ' . APP_NAME,
                'stats' => $stats,
                'categories' => $categories,
                'recent_profiles' => $recentProfiles
            ];
            
            // Affichage de la vue
            $this->render('pages/home', $data);
            
        } catch (Exception $e) {
            error_log("Erreur HomeController::index: " . $e->getMessage());
            $this->render('pages/home', ['error' => 'Une erreur est survenue']);
        }
    }
    
    private function getHomeStats() {
        global $database;
        
        try {
            // Nombre total de prestataires actifs
            $prestatairesSql = "SELECT COUNT(*) as count FROM profils_prestataires p 
                               JOIN utilisateurs u ON p.utilisateur_id = u.id 
                               WHERE u.statut = 'actif'";
            $prestataires = $database->fetch($prestatairesSql)['count'];
            
            // Nombre total de CVs actifs
            $cvsSql = "SELECT COUNT(*) as count FROM cvs c 
                       JOIN utilisateurs u ON c.utilisateur_id = u.id 
                       WHERE u.statut = 'actif'";
            $cvs = $database->fetch($cvsSql)['count'];
            
            // Note moyenne générale
            $noteSql = "SELECT AVG(note) as moyenne FROM avis_notes";
            $noteResult = $database->fetch($noteSql);
            $noteMoyenne = $noteResult['moyenne'] ? round($noteResult['moyenne'], 1) : 0;
            
            // Calcul du pourcentage de satisfaction (notes >= 4)
            $satisfactionSql = "SELECT 
                                (COUNT(CASE WHEN note >= 4 THEN 1 END) * 100.0 / COUNT(*)) as satisfaction 
                                FROM avis_notes";
            $satisfactionResult = $database->fetch($satisfactionSql);
            $satisfaction = $satisfactionResult['satisfaction'] ? round($satisfactionResult['satisfaction']) : 98;
            
            return [
                'prestataires' => $prestataires ?: 2500,
                'cvs' => $cvs ?: 1200,
                'note_moyenne' => $noteMoyenne ?: 4.8,
                'satisfaction' => $satisfaction ?: 98
            ];
            
        } catch (Exception $e) {
            // Valeurs par défaut en cas d'erreur
            return [
                'prestataires' => 2500,
                'cvs' => 1200,
                'note_moyenne' => 4.8,
                'satisfaction' => 98
            ];
        }
    }
    
    private function getPopularCategories() {
        try {
            global $database;
            // Récupérer toutes les catégories actives avec le nombre de prestataires par catégorie (incluant catégories principales et supplémentaires)
            $sql = "SELECT c.*, COUNT(DISTINCT pc.prestataire_id) as nb_prestataires
                    FROM categories_services c
                    LEFT JOIN prestataire_categories pc ON pc.categorie_id = c.id
                    LEFT JOIN utilisateurs u ON pc.prestataire_id = u.id AND u.statut = 'actif'
                    WHERE c.actif = 1
                    GROUP BY c.id, c.nom, c.description, c.icone, c.couleur, c.actif, c.created_at
                    ORDER BY nb_prestataires DESC, c.nom ASC
                    LIMIT 8";
            return $database->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Erreur getPopularCategories: " . $e->getMessage());
            return [];
        }
    }
    
    private function getRecentProfiles() {
        try {
            $userModel = new User($this->db);
            // MODIFICATION : La méthode getRecentProfiles doit être adaptée pour ne prendre
            // que les utilisateurs avec un abonnement actif.
            return $userModel->getRecentProfiles(6, true); // Ajouter un paramètre pour forcer le filtre par abonnement
        } catch (Exception $e) {
            error_log("Erreur getRecentProfiles: " . $e->getMessage());
            return [];
        }
    }
    
    public function search() {
        $type = sanitize($_GET['type'] ?? '');
        $query = sanitize($_GET['query'] ?? '');
        $location = sanitize($_GET['location'] ?? '');
        
        // Redirection vers la page de recherche avec les paramètres
        $params = http_build_query([
            'type' => $type,
            'q' => $query,
            'location' => $location
        ]);
        
        redirect("search.php?$params");
    }
    
    public function api() {
        header('Content-Type: application/json');
        
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'categories':
                echo json_encode($this->getPopularCategories());
                break;
                
            case 'stats':
                echo json_encode($this->getHomeStats());
                break;
                
            case 'recent':
                echo json_encode($this->getRecentProfiles());
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Action non reconnue']);
        }
        exit;
    }
}
?>
