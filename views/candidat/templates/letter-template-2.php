<?php
/**
 * Template Lettre Moderne Style Canva
 */
function renderLetterTemplate2($data) {
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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            line-height: 1.7; 
            color: #2d3748; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .letter-container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            position: relative;
        }
        
        .letter-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem;
            position: relative;
        }
        
        .letter-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(50%, -50%);
        }
        
        .header-content {
            position: relative;
            z-index: 1;
        }
        
        .sender-info h1 { 
            font-size: 2rem; 
            font-weight: 700; 
            margin-bottom: 0.5rem; 
        }
        
        .sender-info p { 
            margin-bottom: 0.3rem; 
            opacity: 0.9;
            font-weight: 300;
        }
        
        .date { 
            position: absolute;
            top: 2.5rem;
            right: 2.5rem;
            font-weight: 500;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .letter-body {
            padding: 2.5rem;
        }
        
        .recipient-info {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }
        
        .recipient-info p {
            margin-bottom: 0.5rem;
        }
        
        .subject { 
            margin-bottom: 2rem;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .letter-content { 
            white-space: pre-line;
            font-size: 1rem;
            line-height: 1.8;
        }
        
        .letter-content p { 
            margin-bottom: 1.5rem; 
            text-align: justify; 
        }
        
        .signature { 
            margin-top: 3rem; 
            text-align: right; 
            font-weight: 600;
            color: #667eea;
            font-size: 1.1rem;
        }
        
        .ai-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            z-index: 2;
        }
        
        .decorative-element {
            position: absolute;
            bottom: 2rem;
            left: 2rem;
            width: 60px;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .letter-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="letter-container">
        <div class="ai-badge">✨ Généré par Mistral IA</div>
        
        <header class="letter-header">
            <div class="header-content">
                <div class="sender-info">
                    <h1><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>
                    <p><?= htmlspecialchars($user['email']) ?></p>
                    <?php if (!empty($user['telephone'])): ?>
                        <p><?= htmlspecialchars($user['telephone']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="date"><?= date('d F Y') ?></div>
        </header>
        
        <div class="letter-body">
            <div class="recipient-info">
                <p><strong>À l'attention de :</strong></p>
                <p><?= htmlspecialchars($job['entreprise'] ?? 'Entreprise') ?></p>
                <p>Service Ressources Humaines</p>
            </div>
            
            <div class="subject">
                <strong>Objet :</strong> Candidature pour le poste de <?= htmlspecialchars($job['poste'] ?? 'poste proposé') ?>
            </div>
            
            <div class="letter-content">
                <?= nl2br(htmlspecialchars($content)) ?>
            </div>
            
            <div class="signature">
                <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
            </div>
            
            <div class="decorative-element"></div>
        </div>
    </div>
</body>
</html>
<?php
    return ob_get_clean();
}
?>