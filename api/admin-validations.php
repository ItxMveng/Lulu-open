<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/middleware-admin.php';
require_admin();

require_once __DIR__ . '/../models/Admin.php';

header('Content-Type: application/json');

// Récupérer données POST
$input = json_decode(file_get_contents('php://input'), true);

error_log('API Validations - Input reçu: ' . json_encode($input));

if (!isset($input['id']) || !isset($input['action'])) {
    error_log('API Validations - Paramètres manquants');
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$demandeId = (int)$input['id'];
$action = $input['action'];
$motif = $input['motif'] ?? '';

$db = Database::getInstance()->getConnection();
$adminModel = new Admin();

try {
    $db->beginTransaction();
    
    // Récupérer la demande
    $stmt = $db->prepare("SELECT * FROM demandes_activation WHERE id = ?");
    $stmt->execute([$demandeId]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$demande) {
        throw new Exception("Demande introuvable");
    }
    
    switch ($action) {
        case 'approuver':
            // Mettre à jour statut demande
            $stmt = $db->prepare("
                UPDATE demandes_activation 
                SET statut = 'approuve', 
                    verifie_par = ?, 
                    date_verification = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $demandeId]);
            
            // Activer le compte utilisateur
            $stmt = $db->prepare("
                UPDATE utilisateurs 
                SET statut = 'actif'
                WHERE id = ?
            ");
            $stmt->execute([$demande['utilisateur_id']]);
            
            // Créer un abonnement si plan demandé
            if ($demande['plan_demande_id']) {
                $stmt = $db->prepare("
                    INSERT INTO abonnements (utilisateur_id, plan_id, date_debut, date_fin, statut, type_abonnement)
                    SELECT ?, id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 'actif', 'mensuel'
                    FROM plans_abonnement 
                    WHERE id = ?
                ");
                $stmt->execute([$demande['utilisateur_id'], $demande['plan_demande_id']]);
            }
            
            // Créer notification utilisateur
            $stmt = $db->prepare("
                INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu, url_action)
                VALUES (?, 'systeme', 'Compte validé', 'Votre compte a été validé ! Vous pouvez maintenant accéder à toutes les fonctionnalités.', '/lulu/dashboard')
            ");
            $stmt->execute([$demande['utilisateur_id']]);
            
            // Logger action admin
            $adminModel->logAction($_SESSION['user_id'], 'validation_compte_approuve', 'demande_activation', $demandeId, [
                'utilisateur_id' => $demande['utilisateur_id'],
                'type_utilisateur' => $demande['type_utilisateur']
            ]);
            
            $message = "Compte approuvé avec succès";
            break;
            
        case 'refuser':
            if (empty($motif)) {
                throw new Exception("Le motif du refus est obligatoire");
            }
            
            // Mettre à jour statut demande
            $stmt = $db->prepare("
                UPDATE demandes_activation 
                SET statut = 'refuse', 
                    verifie_par = ?, 
                    date_verification = NOW(), 
                    motif_refus = ? 
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $motif, $demandeId]);
            
            // Créer notification utilisateur
            $stmt = $db->prepare("
                INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu)
                VALUES (?, 'systeme', 'Demande refusée', ?)
            ");
            $stmt->execute([
                $demande['utilisateur_id'],
                "Votre demande d'activation a été refusée. Motif : $motif"
            ]);
            
            // Logger action admin
            $adminModel->logAction($_SESSION['user_id'], 'validation_compte_refuse', 'demande_activation', $demandeId, [
                'utilisateur_id' => $demande['utilisateur_id'],
                'motif' => $motif
            ]);
            
            $message = "Compte refusé avec succès";
            break;
            
        case 'en_cours':
            error_log('API Validations - Marquage en cours pour demande ID: ' . $demandeId);
            $stmt = $db->prepare("
                UPDATE demandes_activation 
                SET statut = 'en_cours' 
                WHERE id = ?
            ");
            $result = $stmt->execute([$demandeId]);
            $rowCount = $stmt->rowCount();
            error_log('API Validations - Résultat update: ' . ($result ? 'success' : 'failed') . ', lignes affectées: ' . $rowCount);
            
            $message = "Demande marquée en cours";
            break;
            
        default:
            throw new Exception("Action invalide");
    }
    
    $db->commit();
    error_log('API Validations - Transaction commit réussie');
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('API Validations - Erreur: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
