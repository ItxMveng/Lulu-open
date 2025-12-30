<?php
require_once '../config/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $userId = $_SESSION['user_id'];
    $profileType = $_POST['profile_type'] ?? null;

    if (!$profileType || !in_array($profileType, ['prestataire', 'candidat'])) {
        throw new Exception('Type de profil invalide');
    }

    $database = Database::getInstance();

    if ($profileType === 'prestataire') {
        $titre = sanitize($_POST['titre_professionnel'] ?? '');
        $tarif = floatval($_POST['tarif_horaire'] ?? 0);
        $disponibilite = intval($_POST['disponibilite'] ?? 1);
        $description = sanitize($_POST['description_services'] ?? '');

        if (empty($titre) || empty($description)) {
            throw new Exception('Les champs obligatoires doivent être remplis');
        }

        $prestataire = $database->fetch(
            "SELECT id FROM profils_prestataires WHERE utilisateur_id = ?",
            [$userId]
        );

        if ($prestataire) {
            $database->update(
                'profils_prestataires',
                [
                    'titre_professionnel' => $titre,
                    'tarif_horaire' => $tarif,
                    'disponibilite' => $disponibilite,
                    'description_services' => $description,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'utilisateur_id = ?',
                [$userId]
            );
        } else {
            $database->insert('profils_prestataires', [
                'utilisateur_id' => $userId,
                'titre_professionnel' => $titre,
                'tarif_horaire' => $tarif,
                'disponibilite' => $disponibilite,
                'description_services' => $description,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        flashMessage('Profil prestataire mis à jour avec succès', 'success');
    } elseif ($profileType === 'candidat') {
        $titre = sanitize($_POST['titre_poste_recherche'] ?? '');
        $typeContrat = sanitize($_POST['type_contrat'] ?? '');
        $salaire = floatval($_POST['salaire_souhaite'] ?? 0);
        $competences = sanitize($_POST['competences'] ?? '');

        if (empty($titre) || empty($competences)) {
            throw new Exception('Les champs obligatoires doivent être remplis');
        }

        $candidat = $database->fetch(
            "SELECT id FROM cvs WHERE utilisateur_id = ?",
            [$userId]
        );

        if ($candidat) {
            $database->update(
                'cvs',
                [
                    'titre_poste_recherche' => $titre,
                    'type_contrat' => $typeContrat,
                    'salaire_souhaite' => $salaire,
                    'competences' => $competences,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                'utilisateur_id = ?',
                [$userId]
            );
        } else {
            $database->insert('cvs', [
                'utilisateur_id' => $userId,
                'titre_poste_recherche' => $titre,
                'type_contrat' => $typeContrat,
                'salaire_souhaite' => $salaire,
                'competences' => $competences,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        flashMessage('Profil candidat mis à jour avec succès', 'success');
    }

    redirect('../views/prestataire_candidat/dashboard.php');
} catch (Exception $e) {
    flashMessage('Erreur: ' . $e->getMessage(), 'error');
    redirect('../views/prestataire_candidat/dashboard.php');
}
?>
