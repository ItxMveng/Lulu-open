<?php
/**
 * API Admin - Gestion des abonnements
 * Permet le renouvellement et la gestion des abonnements
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/middleware-admin.php';

header('Content-Type: application/json');

// Vérifier que c'est un admin
require_admin();

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'renouveler_gratuit':
            $userId = (int)($input['user_id'] ?? 0);
            if (!$userId) {
                throw new Exception('ID utilisateur manquant');
            }
            
            // Créer un nouvel abonnement gratuit d'1 an
            $start_date = date('Y-m-d H:i:s');
            $end_date = date('Y-m-d H:i:s', strtotime('+1 year'));
            
            $db->beginTransaction();
            
            // Mettre à jour l'utilisateur
            $stmt = $db->prepare("
                UPDATE utilisateurs 
                SET subscription_status = 'Actif',
                    subscription_start_date = ?,
                    subscription_end_date = ?
                WHERE id = ?
            ");
            $stmt->execute([$start_date, $end_date, $userId]);
            
            // Log de l'action admin
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action, cible_type, cible_id, details, created_at)
                VALUES (?, 'renouvellement_gratuit', 'utilisateur', ?, ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $userId,
                json_encode(['duree' => '1 an', 'type' => 'gratuit'])
            ]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Abonnement gratuit renouvelé pour 1 an'
            ]);
            break;
            
        case 'activer_premium':
            $userId = (int)($input['user_id'] ?? 0);
            $plan = $input['plan'] ?? '';
            $duree = (int)($input['duree'] ?? 12); // mois
            
            if (!$userId || !$plan) {
                throw new Exception('Paramètres manquants');
            }
            
            $start_date = date('Y-m-d H:i:s');
            $end_date = date('Y-m-d H:i:s', strtotime("+{$duree} months"));
            
            $db->beginTransaction();
            
            // Mettre à jour l'utilisateur
            $stmt = $db->prepare("
                UPDATE utilisateurs 
                SET subscription_status = 'Actif',
                    subscription_start_date = ?,
                    subscription_end_date = ?
                WHERE id = ?
            ");
            $stmt->execute([$start_date, $end_date, $userId]);
            
            // Créer un enregistrement de paiement manuel
            $stmt = $db->prepare("
                INSERT INTO paiements_stripe (
                    utilisateur_id, plan, montant, status, 
                    stripe_session_id, created_at
                ) VALUES (?, ?, 0.00, 'manual_admin', ?, NOW())
            ");
            $stmt->execute([$userId, $plan, 'admin_activation_' . time()]);
            
            // Log de l'action admin
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action, cible_type, cible_id, details, created_at)
                VALUES (?, 'activation_premium_manuelle', 'utilisateur', ?, ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $userId,
                json_encode(['plan' => $plan, 'duree' => $duree . ' mois'])
            ]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => "Abonnement premium activé pour {$duree} mois"
            ]);
            break;
            
        case 'suspendre_abonnement':
            $userId = (int)($input['user_id'] ?? 0);
            $motif = $input['motif'] ?? 'Suspendu par l\'administrateur';
            
            if (!$userId) {
                throw new Exception('ID utilisateur manquant');
            }
            
            $db->beginTransaction();
            
            // Suspendre l'abonnement
            $stmt = $db->prepare("
                UPDATE utilisateurs 
                SET subscription_status = 'Inactif'
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Créer une notification
            $stmt = $db->prepare("
                INSERT INTO notifications (
                    utilisateur_id, titre, message, type, created_at
                ) VALUES (?, ?, ?, 'warning', NOW())
            ");
            $stmt->execute([
                $userId,
                'Abonnement suspendu',
                'Votre abonnement a été suspendu. Motif: ' . $motif
            ]);
            
            // Log de l'action admin
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action, cible_type, cible_id, details, created_at)
                VALUES (?, 'suspension_abonnement', 'utilisateur', ?, ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $userId,
                json_encode(['motif' => $motif])
            ]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Abonnement suspendu'
            ]);
            break;
            
        case 'reactiver_abonnement':
            $userId = (int)($input['user_id'] ?? 0);
            
            if (!$userId) {
                throw new Exception('ID utilisateur manquant');
            }
            
            $db->beginTransaction();
            
            // Réactiver l'abonnement
            $stmt = $db->prepare("
                UPDATE utilisateurs 
                SET subscription_status = 'Actif'
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            
            // Créer une notification
            $stmt = $db->prepare("
                INSERT INTO notifications (
                    utilisateur_id, titre, message, type, created_at
                ) VALUES (?, ?, ?, 'success', NOW())
            ");
            $stmt->execute([
                $userId,
                'Abonnement réactivé',
                'Votre abonnement a été réactivé par l\'administrateur.'
            ]);
            
            // Log de l'action admin
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action, cible_type, cible_id, details, created_at)
                VALUES (?, 'reactivation_abonnement', 'utilisateur', ?, ?, NOW())
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $userId,
                json_encode(['action' => 'reactivation'])
            ]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Abonnement réactivé'
            ]);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>