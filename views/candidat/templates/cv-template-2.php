<?php
/**
 * Template CV Élégant Sombre - Design 2 - Piloté par JSON IA
 */
function renderCvTemplate2($data) {
    $cvData = $data['cvData'] ?? [];
    $user = $data['user'];
    $cv = $data['cv'];
    
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CV - <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Georgia', serif; line-height: 1.6; color: #f0f0f0; background: #1a1a1a; }
        .cv-container { max-width: 800px; margin: 0 auto; background: #2d2d2d; }
        .cv-header { 
            background: linear-gradient(135deg, #000033, #1a1a2e); 
            color: #f0f0f0; 
            padding: 3rem 2rem; 
        }
        .cv-header h1 { 
            font-size: 2.5rem; 
            font-weight: 300; 
            margin-bottom: 0.5rem; 
            letter-spacing: 2px; 
        }
        .cv-header h2 { 
            font-size: 1.3rem; 
            opacity: 0.8; 
            margin-bottom: 1.5rem; 
            font-style: italic; 
        }
        .contact-info { display: flex; gap: 2rem; flex-wrap: wrap; }
        .cv-content { padding: 2rem; }
        .cv-content section { margin-bottom: 2.5rem; }
        .cv-content h3 { 
            color: #ffd700; 
            font-size: 1.4rem; 
            margin-bottom: 1rem; 
            border-bottom: 1px solid #ffd700; 
            padding-bottom: 0.5rem; 
        }
        .summary-box {
            background: linear-gradient(135deg, #3a3a3a, #2a2a2a);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid #ffd700;
            margin-bottom: 2rem;
        }
        .section-items { list-style: none; }
        .section-items li { 
            padding: 0.5rem 0; 
            border-bottom: 1px solid #444;
            position: relative;
            padding-left: 1.5rem;
        }
        .section-items li:before {
            content: '◆';
            color: #ffd700;
            font-weight: bold;
            position: absolute;
            left: 0;
        }
        .ai-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #000;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="cv-container">
        <div class="ai-badge">✨ Optimisé par Mistral IA</div>
        
        <header class="cv-header">
            <h1><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>
            <h2><?= htmlspecialchars($cvData['title'] ?? $cv['titre_poste_recherche'] ?? 'Professionnel') ?></h2>
            <div class="contact-info">
                <span>✉️ <?= htmlspecialchars($user['email']) ?></span>
                <?php if (!empty($user['telephone'])): ?>
                    <span>☎️ <?= htmlspecialchars($user['telephone']) ?></span>
                <?php endif; ?>
            </div>
        </header>
        
        <main class="cv-content">
            <?php if (!empty($cvData['summary'])): ?>
                <div class="summary-box">
                    <h3>Profil Professionnel</h3>
                    <p><?= nl2br(htmlspecialchars($cvData['summary'])) ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($cvData['sections'])): ?>
                <?php foreach ($cvData['sections'] as $section): ?>
                    <section>
                        <h3><?= htmlspecialchars($section['title']) ?></h3>
                        <ul class="section-items">
                            <?php foreach ($section['items'] as $item): ?>
                                <li><?= htmlspecialchars($item) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
<?php
    return ob_get_clean();
}
?>