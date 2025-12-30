<?php
require_once '../config/config.php';
// Vérification de connexion pour API
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session expirée, veuillez vous reconnecter']);
    exit;
}

if (!in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

header('Content-Type: application/json');

$userId = $_SESSION['user_id'];

try {
    // Validation CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception('Token de sécurité invalide');
    }

    $section = $_POST['section'] ?? '';

    // Récupérer le profil existant
    $profile = $database->fetch("SELECT * FROM cvs WHERE utilisateur_id = ?", [$userId]);
    $user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$userId]);

    $cvData = [];

    switch ($section) {
        case 'general':
            $cvData = [
                'titre_poste_recherche' => trim($_POST['titre_poste_recherche'] ?? ''),
                'categorie_id' => $_POST['categorie_id'] ?? null,
                'niveau_experience' => $_POST['niveau_experience'] ?? '',
                'type_contrat' => $_POST['type_contrat'] ?? '',
                'salaire_souhaite' => $_POST['salaire_souhaite'] ?? null,
                'mobilite' => isset($_POST['mobilite']) ? 1 : 0,
            ];
            break;

        case 'experience':
            $cvData = [
                'experiences_professionnelles' => $_POST['experiences'] ?? '[]',
            ];
            break;

        case 'formation':
            $cvData = [
                'formations' => $_POST['formations'] ?? '[]',
            ];
            break;

        case 'competences':
            $cvData = [
                'competences' => trim($_POST['competences'] ?? ''),
                'langues' => $_POST['langues'] ?? '[]',
            ];
            break;

        case 'documents':
            $cvData = [
                'lettre_motivation' => trim($_POST['lettre_motivation'] ?? ''),
            ];

            // Upload CV
            if (isset($_FILES['cv_fichier']) && $_FILES['cv_fichier']['error'] === UPLOAD_ERR_OK) {
                $cvPath = uploadFile($_FILES['cv_fichier'], 'cvs');
                if ($cvPath) {
                    if ($profile['cv_fichier']) {
                        deleteFile($profile['cv_fichier']);
                    }
                    $cvData['cv_fichier'] = $cvPath;
                }
            }

            // Upload photo de profil
            if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
                $photoPath = uploadFile($_FILES['photo_profil'], 'profiles');
                if ($photoPath) {
                    if ($user['photo_profil']) {
                        deleteFile($user['photo_profil']);
                    }
                    $userUpdateData = ['photo_profil' => $photoPath];
                    $database->update('utilisateurs', $userUpdateData, 'id = ?', [$userId]);
                }
            }
            break;

        default:
            throw new Exception('Section invalide');
    }

    // Sauvegarder les données
    if ($profile) {
        $database->update('cvs', $cvData, 'utilisateur_id = ?', [$userId]);
    } else {
        $cvData['utilisateur_id'] = $userId;
        // Pour nouvel enregistrement, définir les valeurs par défaut
        $defaultData = [
            'utilisateur_id' => $userId,
            'categorie_id' => 1, // Valeur par défaut
            'titre_poste_recherche' => '',
            'niveau_experience' => 'debutant',
            'experience_annees' => 0,
            'salaire_souhaite' => null,
            'devise' => 'EUR',
            'type_contrat' => 'cdi',
            'competences' => '',
            'formations' => '[]',
            'experiences_professionnelles' => '[]',
            'langues' => '[]',
            'mobilite' => 1,
            'disponibilite_immediate' => 1,
            'date_disponibilite' => null,
            'cv_fichier' => null,
            'portfolio_url' => null,
            'lettre_motivation' => '',
            'note_moyenne' => 0.00,
            'nombre_avis' => 0
        ];

        // Fusionner avec les données fournies
        $insertData = array_merge($defaultData, $cvData);
        $database->insert('cvs', $insertData);
    }

    // Recalculer la progression (côté serveur pour cohérence)
    $isComplete = checkProfileCompletion($userId, $_SESSION['user_type'], []);

    // Mettre à jour le statut de complétion
    $database->update('utilisateurs', ['profil_complet' => $isComplete ? 1 : 0], 'id = ?', [$userId]);

    // Activer seulement si complet et pas encore actif
    if ($isComplete && $user['statut'] === 'en_attente') {
        $database->update('utilisateurs', ['statut' => 'actif'], 'id = ?', [$userId]);

        // Notification de félicitations
        $database->insert('messages', [
            'expediteur_id' => 1,
            'destinataire_id' => $userId,
            'sujet' => 'Félicitations ! Votre profil est maintenant complet',
            'contenu' => "Bravo {$user['prenom']} !\n\nVotre profil est maintenant complet et votre compte est actif sur LULU-OPEN. Vous êtes désormais visible par les recruteurs potentiels.\n\nContinuez à optimiser votre CV en ajoutant des expériences et formations pour maximiser vos chances.\n\nL'équipe LULU-OPEN",
            'lu' => 0,
            'date_envoi' => date('Y-m-d H:i:s')
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Section sauvegardée avec succès',
        'complete' => $isComplete
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
