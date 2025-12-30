<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/middleware-admin.php';
require_admin();

require_once __DIR__ . '/../models/Admin.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Action manquante']);
    exit;
}

$action = $input['action'];
$db = Database::getInstance()->getConnection();
$adminModel = new Admin();

try {
    $db->beginTransaction();
    
    switch ($action) {
        case 'details':
            if (!isset($input['id'])) {
                throw new Exception("ID paiement manquant");
            }
            
            $stmt = $db->prepare("
                SELECT p.*, 
                    CONCAT(u.prenom, ' ', u.nom) as nom_utilisateur,
                    u.email, u.photo_profil, u.telephone, u.type_utilisateur,
                    pl.nom as plan_nom,
                    a.date_debut as abo_date_debut,
                    a.date_fin as abo_date_fin
                FROM paiements p
                JOIN utilisateurs u ON p.utilisateur_id = u.id
                LEFT JOIN abonnements a ON p.abonnement_id = a.id
                LEFT JOIN plans_abonnement pl ON a.plan_id = pl.id
                WHERE p.id = ?
            ");
            $stmt->execute([$input['id']]);
            $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$paiement) {
                throw new Exception("Paiement introuvable");
            }
            
            $stmt = $db->prepare("
                SELECT l.*, CONCAT(u.prenom, ' ', u.nom) as admin_nom
                FROM logs_admin l
                JOIN utilisateurs u ON l.admin_id = u.id
                WHERE l.cible_type = 'paiement' AND l.cible_id = ?
                ORDER BY l.created_at DESC
            ");
            $stmt->execute([$input['id']]);
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'paiement' => $paiement,
                'logs' => $logs
            ]);
            break;
            
        case 'valider':
            if (!isset($input['id'])) {
                throw new Exception("ID paiement manquant");
            }
            
            $paiementId = (int)$input['id'];
            
            $stmt = $db->prepare("SELECT * FROM paiements WHERE id = ?");
            $stmt->execute([$paiementId]);
            $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$paiement) {
                throw new Exception("Paiement introuvable");
            }
            
            if ($paiement['statut'] !== 'en_attente') {
                throw new Exception("Ce paiement ne peut pas être validé (statut: {$paiement['statut']})");
            }
            
            $stmt = $db->prepare("
                UPDATE paiements 
                SET statut = 'valide'
                WHERE id = ?
            ");
            $stmt->execute([$paiementId]);
            
            if ($paiement['abonnement_id']) {
                $stmt = $db->prepare("
                    UPDATE abonnements 
                    SET date_fin = DATE_ADD(date_fin, INTERVAL 1 MONTH),
                        statut = 'actif'
                    WHERE id = ?
                ");
                $stmt->execute([$paiement['abonnement_id']]);
            }
            
            $stmt = $db->prepare("
                INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu)
                VALUES (?, 'systeme', 'Paiement validé', ?)
            ");
            $stmt->execute([
                $paiement['utilisateur_id'],
                "Votre paiement de {$paiement['montant']}€ a été validé. Merci !"
            ]);
            
            $adminModel->logAction($_SESSION['user_id'], 'validation_paiement_manuel', 'paiement', $paiementId, [
                'montant' => $paiement['montant']
            ]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Paiement validé avec succès'
            ]);
            break;
            
        case 'rembourser':
            if (!isset($input['id']) || !isset($input['motif']) || !isset($input['type'])) {
                throw new Exception("Paramètres manquants");
            }
            
            $paiementId = (int)$input['id'];
            $type = $input['type'];
            $motif = $input['motif'];
            $motifTexte = $input['motif_texte'] ?? '';
            $notifier = $input['notifier'] ?? true;
            
            $stmt = $db->prepare("SELECT * FROM paiements WHERE id = ?");
            $stmt->execute([$paiementId]);
            $paiement = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$paiement) {
                throw new Exception("Paiement introuvable");
            }
            
            if ($paiement['statut'] !== 'valide') {
                throw new Exception("Seuls les paiements validés peuvent être remboursés");
            }
            
            if ($paiement['statut'] === 'rembourse') {
                throw new Exception("Ce paiement a déjà été remboursé");
            }
            
            if ($type === 'total') {
                $montantRemboursement = $paiement['montant'];
            } else {
                $montantRemboursement = (float)$input['montant'];
                if ($montantRemboursement <= 0 || $montantRemboursement > $paiement['montant']) {
                    throw new Exception("Montant invalide");
                }
            }
            
            $stmt = $db->prepare("
                UPDATE paiements 
                SET statut = 'rembourse'
                WHERE id = ?
            ");
            $stmt->execute([$paiementId]);
            
            if ($type === 'total' && $paiement['abonnement_id']) {
                $stmt = $db->prepare("UPDATE abonnements SET statut = 'suspendu' WHERE id = ?");
                $stmt->execute([$paiement['abonnement_id']]);
            }
            
            if ($notifier) {
                $stmt = $db->prepare("
                    INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu)
                    VALUES (?, 'systeme', 'Remboursement effectué', ?)
                ");
                $stmt->execute([
                    $paiement['utilisateur_id'],
                    "Un remboursement de {$montantRemboursement}€ a été effectué sur votre compte. Motif : $motif."
                ]);
            }
            
            $adminModel->logAction($_SESSION['user_id'], 'remboursement_paiement', 'paiement', $paiementId, [
                'type' => $type,
                'montant' => $montantRemboursement,
                'motif' => $motif
            ]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Remboursement de {$montantRemboursement}€ effectué avec succès"
            ]);
            break;
            
        default:
            throw new Exception("Action inconnue");
    }
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
