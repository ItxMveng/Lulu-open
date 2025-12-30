<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Accès refusé');
}

require_once __DIR__ . '/../models/Subscription.php';

$subscription = new Subscription();
$notifiedCount = $subscription->checkExpirationAndNotify();

$logFile = __DIR__ . '/../scripts/expiration_log.txt';
$logs = file_exists($logFile) ? file_get_contents($logFile) : 'Aucun log disponible';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cron Manual - Admin</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .logs { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Exécution Manuelle du Cron</h1>
        <div class="success">
            ✅ Cron exécuté avec succès !<br>
            Utilisateurs notifiés: <strong><?= $notifiedCount ?></strong>
        </div>
        <h3>📋 Logs d'exécution</h3>
        <div class="logs"><?= htmlspecialchars($logs) ?></div>
        <br>
        <a href="dashboard.php" class="btn">← Retour au Dashboard</a>
        <a href="run-cron.php" class="btn" style="background:#28a745;">🔄 Réexécuter</a>
    </div>
</body>
</html>
