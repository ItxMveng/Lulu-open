<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page non trouvée - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #000033;
            --primary-blue: #0099FF;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-blue) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            text-align: center;
            max-width: 600px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-blue), #00CCFF);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }
        .error-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .suggestions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        .suggestions h5 {
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }
        .suggestions ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .suggestions li {
            padding: 0.5rem 0;
        }
        .suggestions a {
            color: var(--primary-blue);
            text-decoration: none;
            transition: all 0.3s;
        }
        .suggestions a:hover {
            color: var(--primary-dark);
            padding-left: 5px;
        }
        .btn-home {
            background: linear-gradient(135deg, var(--primary-blue), #00CCFF);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 153, 255, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-title">Page non trouvée</h1>
        <p class="error-message">
            Désolé, la page que vous recherchez n'existe pas ou a été déplacée.
        </p>
        
        <div class="suggestions">
            <h5><i class="bi bi-lightbulb"></i> Suggestions :</h5>
            <ul>
                <li><i class="bi bi-arrow-right text-primary"></i> <a href="/lulu/">Retour à l'accueil</a></li>
                <li><i class="bi bi-arrow-right text-primary"></i> <a href="/lulu/services.php">Parcourir les services</a></li>
                <li><i class="bi bi-arrow-right text-primary"></i> <a href="/lulu/emplois.php">Découvrir les emplois</a></li>
                <li><i class="bi bi-arrow-right text-primary"></i> <a href="/lulu/contact">Nous contacter</a></li>
            </ul>
        </div>
        
        <a href="/lulu/" class="btn btn-home">
            <i class="bi bi-house-door"></i> Retour à l'accueil
        </a>
    </div>
</body>
</html>
