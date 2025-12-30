<?php
/**
 * Script de remplissage de données de test
 * Pour tester les fonctionnalités admin
 */

require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance()->getConnection();

echo "=== REMPLISSAGE BASE DE DONNÉES TEST ===\n\n";

try {
    $db->beginTransaction();
    
    // 1. Créer des utilisateurs de test
    echo "1. Création utilisateurs de test...\n";
    
    $users = [
        ['prenom' => 'Jean', 'nom' => 'Dupont', 'email' => 'jean.dupont@test.com', 'type' => 'prestataire'],
        ['prenom' => 'Marie', 'nom' => 'Martin', 'email' => 'marie.martin@test.com', 'type' => 'candidat'],
        ['prenom' => 'Pierre', 'nom' => 'Bernard', 'email' => 'pierre.bernard@test.com', 'type' => 'prestataire'],
        ['prenom' => 'Sophie', 'nom' => 'Dubois', 'email' => 'sophie.dubois@test.com', 'type' => 'candidat'],
        ['prenom' => 'Luc', 'nom' => 'Moreau', 'email' => 'luc.moreau@test.com', 'type' => 'prestataire'],
    ];
    
    $userIds = [];
    foreach ($users as $user) {
        $stmt = $db->prepare("
            INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, type_utilisateur, statut, date_inscription)
            VALUES (?, ?, ?, ?, ?, 'actif', NOW())
        ");
        $stmt->execute([
            $user['prenom'],
            $user['nom'],
            $user['email'],
            password_hash('Test123!', PASSWORD_DEFAULT),
            $user['type']
        ]);
        $userIds[] = $db->lastInsertId();
        echo "  - {$user['prenom']} {$user['nom']} créé (ID: {$db->lastInsertId()})\n";
    }
    
    // 2. Créer des abonnements
    echo "\n2. Création abonnements...\n";
    
    // Récupérer les plans existants
    $stmt = $db->query("SELECT id FROM plans_abonnement LIMIT 3");
    $plans = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($plans)) {
        echo "  ERREUR: Aucun plan trouvé. Créez d'abord des plans.\n";
    } else {
        $abonnementIds = [];
        foreach ($userIds as $index => $userId) {
            $planId = $plans[array_rand($plans)];
            $typeAbo = ['mensuel', 'trimestriel', 'annuel'][array_rand(['mensuel', 'trimestriel', 'annuel'])];
            $prix = ['mensuel' => 29.99, 'trimestriel' => 79.99, 'annuel' => 299.99][$typeAbo];
            $statut = ['actif', 'actif', 'actif', 'suspendu', 'expire'][array_rand(['actif', 'actif', 'actif', 'suspendu', 'expire'])];
            
            $dateDebut = date('Y-m-d', strtotime("-{$index} months"));
            $dateFin = date('Y-m-d', strtotime("+1 month", strtotime($dateDebut)));
            
            $stmt = $db->prepare("
                INSERT INTO abonnements (utilisateur_id, plan_id, type_abonnement, prix, date_debut, date_fin, statut, auto_renouvellement)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $planId, $typeAbo, $prix, $dateDebut, $dateFin, $statut, rand(0, 1)]);
            $abonnementIds[] = $db->lastInsertId();
            echo "  - Abonnement créé pour user $userId (ID: {$db->lastInsertId()}, Statut: $statut)\n";
        }
        
        // 3. Créer des paiements
        echo "\n3. Création paiements...\n";
        
        $methodes = ['stripe', 'paypal', 'virement'];
        $statuts = ['valide', 'valide', 'valide', 'en_attente', 'echoue'];
        
        foreach ($abonnementIds as $index => $aboId) {
            $userId = $userIds[$index];
            $methode = $methodes[array_rand($methodes)];
            $statut = $statuts[array_rand($statuts)];
            $montant = rand(2999, 29999) / 100;
            
            $stmt = $db->prepare("
                INSERT INTO paiements (utilisateur_id, abonnement_id, montant, methode_paiement, statut, transaction_id, date_paiement)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $transactionId = $statut === 'valide' ? 'TXN_' . strtoupper(bin2hex(random_bytes(8))) : null;
            $stmt->execute([$userId, $aboId, $montant, $methode, $statut, $transactionId]);
            echo "  - Paiement créé: {$montant}€ via $methode (Statut: $statut)\n";
            
            // Ajouter quelques paiements supplémentaires pour certains utilisateurs
            if ($index < 2) {
                $stmtHist = $db->prepare("
                    INSERT INTO paiements (utilisateur_id, abonnement_id, montant, methode_paiement, statut, transaction_id, date_paiement)
                    VALUES (?, ?, ?, ?, 'valide', ?, ?)
                ");
                $stmtHist->execute([
                    $userId, 
                    $aboId, 
                    $montant, 
                    $methodes[array_rand($methodes)], 
                    'TXN_' . strtoupper(bin2hex(random_bytes(8))),
                    date('Y-m-d H:i:s', strtotime('-1 month'))
                ]);
                echo "  - Paiement historique créé: {$montant}€\n";
            }
        }
        
        // 4. Créer des demandes d'activation
        echo "\n4. Création demandes d'activation...\n";
        
        $typesUtilisateur = ['prestataire', 'candidat', 'prestataire_candidat'];
        $statutsDemande = ['en_attente', 'en_attente', 'en_cours', 'approuve', 'refuse'];
        
        foreach (array_slice($userIds, 0, 3) as $userId) {
            $type = $typesUtilisateur[array_rand($typesUtilisateur)];
            $statutDemande = $statutsDemande[array_rand($statutsDemande)];
            $planId = $plans[array_rand($plans)];
            
            $stmt = $db->prepare("
                INSERT INTO demandes_activation (utilisateur_id, type_utilisateur, plan_demande_id, statut, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $type, $planId, $statutDemande]);
            echo "  - Demande créée pour user $userId (Type: $type, Statut: $statutDemande)\n";
        }
        
        // 5. Créer des notifications
        echo "\n5. Création notifications...\n";
        
        $notifications = [
            ['titre' => 'Bienvenue !', 'contenu' => 'Bienvenue sur LULU-OPEN !'],
            ['titre' => 'Paiement reçu', 'contenu' => 'Votre paiement a été reçu avec succès.'],
            ['titre' => 'Abonnement activé', 'contenu' => 'Votre abonnement est maintenant actif.'],
        ];
        
        foreach ($userIds as $userId) {
            $notif = $notifications[array_rand($notifications)];
            $stmt = $db->prepare("
                INSERT INTO notifications (utilisateur_id, type_notification, titre, contenu, lu, created_at)
                VALUES (?, 'systeme', ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $notif['titre'], $notif['contenu'], rand(0, 1)]);
        }
        echo "  - " . count($userIds) . " notifications créées\n";
    }
    
    $db->commit();
    
    echo "\n=== REMPLISSAGE TERMINÉ AVEC SUCCÈS ===\n";
    echo "\nRésumé:\n";
    echo "- " . count($userIds) . " utilisateurs créés\n";
    echo "- " . (isset($abonnementIds) ? count($abonnementIds) : 0) . " abonnements créés\n";
    echo "- Paiements et demandes d'activation créés\n";
    echo "\nVous pouvez maintenant tester les fonctionnalités admin !\n";
    echo "\nIdentifiants de test:\n";
    foreach ($users as $user) {
        echo "  Email: {$user['email']} | Mot de passe: Test123!\n";
    }
    
} catch (Exception $e) {
    $db->rollBack();
    echo "\nERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
