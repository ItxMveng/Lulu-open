<?php
session_start();
session_destroy();
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session nettoyée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card">
            <div class="card-body text-center">
                <h1 class="text-success mb-4">✅ Session nettoyée</h1>
                <p class="lead">La session a été réinitialisée avec succès.</p>
                <p>Vous pouvez maintenant tester l'inscription.</p>
                <div class="mt-4">
                    <a href="/lulu/register.php?type=client&step=1" class="btn btn-primary btn-lg">
                        Tester l'inscription Client
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
