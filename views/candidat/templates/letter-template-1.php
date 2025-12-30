<?php
/**
 * Template Lettre Professionnelle - Design 1
 */
function renderLetterTemplate1($data) {
    $content = $data['content'];
    $user = $data['user'];
    $job = $data['job'];
    
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Lettre de Motivation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; line-height: 1.6; color: #333; background: white; }
        .letter-container { max-width: 700px; margin: 2rem auto; padding: 3rem; background: white; }
        .letter-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: flex-start; 
            margin-bottom: 2rem; 
        }
        .sender-info h1 { font-size: 1.5rem; color: #0066CC; margin-bottom: 0.5rem; }
        .sender-info p { margin-bottom: 0.3rem; }
        .date { font-weight: bold; }
        .recipient-info, .subject { margin-bottom: 1.5rem; }
        .subject { font-weight: bold; }
        .letter-content { white-space: pre-line; }
        .letter-content p { margin-bottom: 1.2rem; text-align: justify; }
        .signature { margin-top: 2rem; text-align: right; font-weight: bold; }
    </style>
</head>
<body>
    <div class="letter-container">
        <header class="letter-header">
            <div class="sender-info">
                <h1><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>
                <p><?= htmlspecialchars($user['email']) ?></p>
                <?php if (!empty($user['telephone'])): ?>
                    <p><?= htmlspecialchars($user['telephone']) ?></p>
                <?php endif; ?>
            </div>
            <div class="date"><?= date('d F Y') ?></div>
        </header>
        
        <div class="recipient-info">
            <p><strong>À l'attention de :</strong></p>
            <p><?= htmlspecialchars($job['entreprise'] ?? 'Entreprise') ?></p>
            <p>Service Ressources Humaines</p>
        </div>
        
        <div class="subject">
            <p><strong>Objet :</strong> Candidature pour le poste de <?= htmlspecialchars($job['poste'] ?? 'poste proposé') ?> - Générée par Mistral IA</p>
        </div>
        
        <div class="letter-content">
            <?= nl2br(htmlspecialchars($content)) ?>
        </div>
    </div>
</body>
</html>
<?php
    return ob_get_clean();
}
?>