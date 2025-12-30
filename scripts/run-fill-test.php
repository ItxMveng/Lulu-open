<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Remplissage BD Test - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        .output { background: #000; color: #0f0; padding: 1rem; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🔧 Remplissage Base de Données Test</h1>
        
        <div class="alert alert-warning">
            <strong>⚠️ Attention :</strong> Ce script va créer des données de test dans votre base de données.
        </div>
        
        <button id="runScript" class="btn btn-primary btn-lg mb-3">
            <i class="bi bi-play-fill"></i> Exécuter le script
        </button>
        
        <div id="output" class="output" style="display: none;"></div>
    </div>

    <script>
        document.getElementById('runScript').addEventListener('click', async function() {
            const btn = this;
            const output = document.getElementById('output');
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Exécution...';
            output.style.display = 'block';
            output.textContent = 'Démarrage du script...\n\n';
            
            try {
                const response = await fetch('fill-test-data.php');
                const text = await response.text();
                output.textContent = text;
                btn.innerHTML = '✅ Script terminé';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-success');
            } catch (error) {
                output.textContent = 'ERREUR: ' + error.message;
                btn.innerHTML = '❌ Erreur';
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-danger');
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
