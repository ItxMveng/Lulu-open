<?php
session_start();
require_once 'config/config.php';
require_once 'config/db.php';

echo "<h2>Vérification du message de bienvenue</h2>";

global $database;

// Vérifier si l'utilisateur admin existe
$admin = $database->fetch("SELECT id FROM utilisateurs WHERE type_utilisateur = 'admin' LIMIT 1");
$adminId = $admin['id'] ?? 1;

// Vérifier les messages de bienvenue existants
$messages = $database->fetchAll("SELECT * FROM messages WHERE expediteur_id = ? AND sujet LIKE '%Bienvenue%'", [$adminId]);

echo "<p>Messages de bienvenue trouvés : " . count($messages) . "</p>";

if (count($messages) > 0) {
    echo "<h3>Messages existants :</h3>";
    foreach ($messages as $msg) {
        $user = $database->fetch("SELECT prenom, nom FROM utilisateurs WHERE id = ?", [$msg['destinataire_id']]);
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Pour :</strong> " . ($user['prenom'] ?? 'Inconnu') . " " . ($user['nom'] ?? '') . "<br>";
        echo "<strong>Sujet :</strong> " . htmlspecialchars($msg['sujet']) . "<br>";
        echo "<strong>Date :</strong> " . $msg['date_envoi'] . "<br>";
        echo "<strong>Contenu :</strong><br>" . nl2br(htmlspecialchars(substr($msg['contenu'], 0, 200))) . "...";
        echo "</div>";
    }
} else {
    echo "<p style='color: orange;'>Aucun message de bienvenue trouvé.</p>";
}

echo "<h3>Test de création de message de bienvenue :</h3>";

// Fonction pour créer un message de bienvenue
function createWelcomeMessage($database, $userId, $prenom, $type) {
    $adminId = 1;
    
    $messageBienvenue = "Bienvenue sur LULU-OPEN, $prenom !\n\n";
    $messageBienvenue .= "Vous êtes actuellement sur le plan gratuit de base.\n\n";
    $messageBienvenue .= "Pour être visible sur la plateforme et bénéficier de toutes les fonctionnalités :\n";
    $messageBienvenue .= "1. Complétez votre profil (photo, description, compétences)\n";
    $messageBienvenue .= "2. Une fois validé, votre compte deviendra actif\n\n";
    $messageBienvenue .= "Pour des fonctionnalités avancées (mise en avant, statistiques, support prioritaire), ";
    $messageBienvenue .= "découvrez nos abonnements Premium dans la section Abonnements.\n\n";
    $messageBienvenue .= "L'équipe LULU-OPEN";

    return $database->insert('messages', [
        'expediteur_id' => $adminId,
        'destinataire_id' => $userId,
        'sujet' => 'Bienvenue sur LULU-OPEN !',
        'contenu' => $messageBienvenue,
        'lu' => 0,
        'date_envoi' => date('Y-m-d H:i:s')
    ]);
}

// Tester avec un utilisateur candidat
$candidat = $database->fetch("SELECT * FROM utilisateurs WHERE type_utilisateur = 'candidat' LIMIT 1");

if ($candidat) {
    // Vérifier s'il a déjà un message de bienvenue
    $existingMsg = $database->fetch("SELECT id FROM messages WHERE destinataire_id = ? AND sujet LIKE '%Bienvenue%'", [$candidat['id']]);
    
    if (!$existingMsg) {
        $msgId = createWelcomeMessage($database, $candidat['id'], $candidat['prenom'], $candidat['type_utilisateur']);
        echo "<p style='color: green;'>✅ Message de bienvenue créé pour " . $candidat['prenom'] . " (ID: $msgId)</p>";
    } else {
        echo "<p style='color: blue;'>ℹ️ Message de bienvenue déjà existant pour " . $candidat['prenom'] . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Aucun candidat trouvé pour le test</p>";
}

echo "<p><a href='views/candidat/dashboard.php'>🔗 Tester le dashboard candidat</a></p>";
?>