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
            if (empty($input['id'])) {
                throw new Exception("ID utilisateur manquant");
            }
            $userId = (int)$input['id'];

            $stmt = $db->prepare("
                SELECT u.*,
                    (SELECT COUNT(*) FROM abonnements a WHERE a.utilisateur_id = u.id) as total_abonnements,
                    (SELECT COUNT(*) FROM abonnements a WHERE a.utilisateur_id = u.id AND a.statut IN ('actif','essai')) as abonnements_actifs,
                    (SELECT COUNT(*) FROM paiements p WHERE p.utilisateur_id = u.id AND p.statut = 'valide') as paiements_valides,
                    (SELECT COUNT(*) FROM demandes_activation d WHERE d.utilisateur_id = u.id) as demandes_total
                FROM utilisateurs u
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }

            $stmt = $db->prepare("
                SELECT id, plan_id, date_debut, date_fin, statut 
                FROM abonnements 
                WHERE utilisateur_id = ? 
                ORDER BY date_debut DESC 
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $abos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("
                SELECT id, montant, statut, date_paiement, methode_paiement
                FROM paiements
                WHERE utilisateur_id = ?
                ORDER BY date_paiement DESC
                LIMIT 5
            ");
            $stmt->execute([$userId]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les catégories de l'utilisateur
            $categories = [];
            if (in_array($user['type_utilisateur'], ['prestataire', 'prestataire_candidat'])) {
                $stmt = $db->prepare("
                    SELECT c.nom, c.icone, c.couleur
                    FROM profils_prestataires pp
                    LEFT JOIN categories_services c ON pp.categorie_id = c.id
                    WHERE pp.utilisateur_id = ? AND pp.categorie_id IS NOT NULL AND c.id IS NOT NULL
                    ORDER BY c.nom
                ");
                $stmt->execute([$userId]);
                $prestataireCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $categories = array_merge($categories, $prestataireCategories);
            }
            if (in_array($user['type_utilisateur'], ['candidat', 'prestataire_candidat'])) {
                $stmt = $db->prepare("
                    SELECT c.nom, c.icone, c.couleur
                    FROM cvs cv
                    LEFT JOIN categories_services c ON cv.categorie_id = c.id
                    WHERE cv.utilisateur_id = ? AND cv.categorie_id IS NOT NULL AND c.id IS NOT NULL
                    ORDER BY c.nom
                ");
                $stmt->execute([$userId]);
                $candidatCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $categories = array_merge($categories, $candidatCategories);
            }
            // Supprimer les doublons
            $categories = array_unique($categories, SORT_REGULAR);

            $db->commit();
            echo json_encode([
                'success' => true,
                'user' => $user,
                'abonnements' => $abos,
                'paiements' => $payments,
                'categories' => $categories,
            ]);
            break;

        case 'change_status':
            if (empty($input['id']) || empty($input['new_status'])) {
                throw new Exception("Paramètres manquants");
            }

            $userId = (int)$input['id'];
            $newStatus = $input['new_status'];
            $reason = $input['reason'] ?? null;

            $allowedStatuses = ['actif', 'suspendu', 'bloque'];
            if (!in_array($newStatus, $allowedStatuses, true)) {
                throw new Exception("Statut invalide");
            }

            if ($userId === (int)$_SESSION['user_id']) {
                throw new Exception("Vous ne pouvez pas modifier votre propre statut");
            }

            $stmt = $db->prepare("SELECT id, statut, email FROM utilisateurs WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }

            $stmt = $db->prepare("
                UPDATE utilisateurs
                SET statut = ?
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $userId]);

            $adminModel->logAction($_SESSION['user_id'], 'change_status_user', 'utilisateur', $userId, [
                'ancien_statut' => $user['statut'],
                'nouveau_statut' => $newStatus,
                'raison' => $reason
            ]);

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Statut mis à jour en '$newStatus'"
            ]);
            break;

        case 'reset_password':
            if (empty($input['id'])) {
                throw new Exception("ID utilisateur manquant");
            }

            $userId = (int)$input['id'];

            if ($userId === (int)$_SESSION['user_id']) {
                throw new Exception("Vous ne pouvez pas réinitialiser votre propre mot de passe ici");
            }

            $stmt = $db->prepare("SELECT id, email, prenom, nom FROM utilisateurs WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }

            $tempPassword = bin2hex(random_bytes(4));
            $passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                UPDATE utilisateurs 
                SET mot_de_passe = ?
                WHERE id = ?
            ");
            $stmt->execute([$passwordHash, $userId]);

            $adminModel->logAction($_SESSION['user_id'], 'reset_password_user', 'utilisateur', $userId, [
                'email' => $user['email']
            ]);

            $db->commit();
            echo json_encode([
                'success' => true,
                'message' => "Mot de passe réinitialisé. Un mot de passe temporaire a été généré.",
                'temp_password' => $tempPassword
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
