<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/middleware-admin.php';

try {
    require_admin();
    $db = Database::getInstance()->getConnection();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? null;
    
    switch ($action) {
        case 'create':
            $nom = trim($_POST['nom'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $icone = trim($_POST['icone'] ?? '');
            
            if (empty($nom)) {
                throw new Exception('Le nom est requis');
            }
            
            $stmt = $db->prepare("INSERT INTO categories_services (nom, description, icone) VALUES (?, ?, ?)");
            $stmt->execute([$nom, $description ?: null, $icone ?: null]);
            
            header('Location: ../views/admin/categories.php?success=1');
            exit;
            
        case 'update':
            $id = $input['id'] ?? 0;
            $nom = trim($input['nom'] ?? '');
            $description = trim($input['description'] ?? '');
            $icone = trim($input['icone'] ?? '');
            
            if (empty($nom) || !$id) {
                throw new Exception('Données invalides');
            }
            
            $stmt = $db->prepare("UPDATE categories_services SET nom = ?, description = ?, icone = ? WHERE id = ?");
            $stmt->execute([$nom, $description ?: null, $icone ?: null, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Catégorie mise à jour']);
            break;
            
        case 'delete':
            $id = $input['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID invalide');
            }
            
            // Vérifier si la catégorie est utilisée par des utilisateurs
            $totalUsers = $db->prepare("
                SELECT COUNT(DISTINCT user_id) FROM (
                    SELECT utilisateur_id as user_id FROM profils_prestataires WHERE categorie_id = ?
                    UNION
                    SELECT utilisateur_id as user_id FROM cvs WHERE categorie_id = ?
                ) as combined_users
            ");
            $totalUsers->execute([$id, $id]);
            
            if ($totalUsers->fetchColumn() > 0) {
                throw new Exception('Cette catégorie est utilisée par des utilisateurs');
            }
            
            $stmt = $db->prepare("DELETE FROM categories_services WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Catégorie supprimée']);
            break;
            
        case 'get':
            $id = $input['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID invalide');
            }
            
            $stmt = $db->prepare("SELECT * FROM categories_services WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) {
                throw new Exception('Catégorie non trouvée');
            }
            
            echo json_encode(['success' => true, 'data' => $category]);
            break;
            
        case 'get_users':
            $id = $input['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID invalide');
            }
            
            // Récupérer tous les utilisateurs liés à cette catégorie avec UNION
            $allUsers = $db->prepare("
                SELECT u.id, u.prenom, u.nom, u.email, u.statut, u.type_utilisateur, 'prestataire' as source
                FROM utilisateurs u 
                JOIN profils_prestataires p ON u.id = p.utilisateur_id 
                WHERE p.categorie_id = ?
                
                UNION
                
                SELECT u.id, u.prenom, u.nom, u.email, u.statut, u.type_utilisateur, 'candidat' as source
                FROM utilisateurs u 
                JOIN cvs c ON u.id = c.utilisateur_id 
                WHERE c.categorie_id = ?
                
                ORDER BY prenom, nom
            ");
            $allUsers->execute([$id, $id]);
            $users = $allUsers->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'users' => $users]);
            break;
            
        default:
            throw new Exception('Action non valide');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>