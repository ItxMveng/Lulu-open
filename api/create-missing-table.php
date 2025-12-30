<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer Table Paiements</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 700px;
            width: 100%;
        }
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 5px solid #28a745;
            font-size: 18px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 5px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 5px solid #17a2b8;
        }
        button {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            font-weight: bold;
        }
        button:hover {
            background: #218838;
        }
        .link {
            text-align: center;
            margin-top: 20px;
        }
        .link a {
            background: #667eea;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
        }
        .link a:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Créer Table Manquante: PAIEMENTS</h1>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../config/db.php';
            
            try {
                $db = Database::getInstance();
                $pdo = $db->getConnection();
                
                $sql = "CREATE TABLE IF NOT EXISTS `paiements` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `abonnement_id` INT NOT NULL,
                    `montant` DECIMAL(10,2) NOT NULL,
                    `devise` VARCHAR(3) DEFAULT 'EUR',
                    `methode_paiement` VARCHAR(50) NOT NULL,
                    `date_paiement` DATETIME NOT NULL,
                    `statut` ENUM('en_attente','valide','refuse','rembourse') DEFAULT 'valide',
                    `reference_transaction` VARCHAR(100) NULL,
                    `notes` TEXT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    KEY `idx_abonnement` (`abonnement_id`),
                    KEY `idx_statut` (`statut`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";
                
                $pdo->exec($sql);
                
                // Vérification
                $stmt = $pdo->query("SHOW TABLES LIKE 'paiements'");
                $exists = $stmt->fetch();
                
                if ($exists) {
                    echo '<div class="success">';
                    echo '<h2>✅ SUCCÈS TOTAL!</h2>';
                    echo '<p>La table <strong>paiements</strong> a été créée avec succès!</p>';
                    echo '<p>Vous pouvez maintenant:</p>';
                    echo '<ol>';
                    echo '<li>Fermer cette page</li>';
                    echo '<li>Retourner à l\'interface admin</li>';
                    echo '<li>Vérifier les abonnements SANS ERREUR</li>';
                    echo '</ol>';
                    echo '</div>';
                    
                    echo '<div class="link">';
                    echo '<a href="../views/admin/subscriptions-unified.php">Aller à l\'interface admin →</a>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<h3>⚠️ Erreur</h3>';
                    echo '<p>La table n\'a pas pu être créée.</p>';
                    echo '</div>';
                }
                
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') !== false) {
                    echo '<div class="success">';
                    echo '<h2>✅ TABLE EXISTE DÉJÀ!</h2>';
                    echo '<p>La table <strong>paiements</strong> existe déjà dans la base de données.</p>';
                    echo '<p>Tout est OK! Vous pouvez utiliser l\'interface admin normalement.</p>';
                    echo '</div>';
                    
                    echo '<div class="link">';
                    echo '<a href="../views/admin/subscriptions-unified.php">Aller à l\'interface admin →</a>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '<h3>❌ Erreur SQL</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '</div>';
                }
            }
        } else {
            ?>
            <div class="info">
                <h3>🔍 Problème Détecté:</h3>
                <p><strong>Erreur actuelle:</strong> "Champ 'devise' inconnu dans field list"</p>
                <p><strong>Cause:</strong> La table <code>paiements</code> n'existe pas dans votre base de données.</p>
                <p><strong>Solution:</strong> Créer cette table maintenant.</p>
            </div>
            
            <div class="info">
                <h3>📋 Ce qui va se passer:</h3>
                <p>✅ Création de la table <code>paiements</code></p>
                <p>✅ Avec toutes les colonnes nécessaires (id, montant, devise, etc.)</p>
                <p>✅ Temps d'exécution: 2 secondes</p>
            </div>
            
            <form method="POST">
                <button type="submit">🚀 CRÉER LA TABLE MAINTENANT</button>
            </form>
            <?php
        }
        ?>
    </div>
</body>
</html>
