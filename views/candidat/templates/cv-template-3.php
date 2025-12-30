<?php
/**
 * Template CV Canva Style - Design Moderne
 */
function renderCvTemplate3($data) {
    $cvData = $data['cvData'] ?? [];
    $user = $data['user'] ?? [];
    $cv = $data['cv'] ?? [];
    
    ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CV - <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            line-height: 1.6; 
            color: #2d3748;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        
        .cv-container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            display: grid;
            grid-template-columns: 300px 1fr;
        }
        
        .cv-sidebar {
            background: linear-gradient(180deg, #2d3748 0%, #1a202c 100%);
            color: white;
            padding: 2.5rem 2rem;
        }
        
        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #4a5568;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            border: 4px solid rgba(255,255,255,0.2);
        }
        
        .profile-name {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-name h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .profile-name .title {
            font-size: 0.9rem;
            opacity: 0.8;
            font-weight: 300;
        }
        
        .sidebar-section {
            margin-bottom: 2rem;
        }
        
        .sidebar-section h3 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
            color: #a0aec0;
            font-weight: 600;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            font-size: 0.85rem;
        }
        
        .contact-icon {
            width: 16px;
            height: 16px;
            margin-right: 0.8rem;
            opacity: 0.7;
        }
        
        .cv-main {
            padding: 2.5rem;
        }
        
        .main-header {
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .main-header h2 {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .main-header .subtitle {
            font-size: 1.1rem;
            color: #667eea;
            font-weight: 500;
        }
        
        .content-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            margin-right: 0.8rem;
            border-radius: 2px;
        }
        
        .content-text {
            white-space: pre-line;
            font-size: 0.95rem;
            line-height: 1.7;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #667eea;
            margin: 1rem 0;
        }
        
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }
        
        .skill-tag {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .ai-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, #48bb78, #38a169);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        @media print {
            body { background: white; padding: 0; }
            .cv-container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="cv-container">
        <div class="ai-badge">✨ Optimisé par Mistral IA</div>
        
        <div class="cv-sidebar">
            <div class="profile-photo">
                👤
            </div>
            
            <div class="profile-name">
                <h1><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>
                <div class="title"><?= htmlspecialchars($cv['titre_poste_recherche'] ?? 'Professionnel') ?></div>
            </div>
            
            <div class="sidebar-section">
                <h3>Contact</h3>
                <div class="contact-item">
                    <span class="contact-icon">📧</span>
                    <?= htmlspecialchars($user['email']) ?>
                </div>
                <?php if (!empty($user['telephone'])): ?>
                    <div class="contact-item">
                        <span class="contact-icon">📞</span>
                        <?= htmlspecialchars($user['telephone']) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($cv['linkedin'])): ?>
                    <div class="contact-item">
                        <span class="contact-icon">💼</span>
                        LinkedIn
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-section">
                <h3>Compétences</h3>
                <div class="skills-grid">
                    <div class="skill-tag">Leadership</div>
                    <div class="skill-tag">Innovation</div>
                    <div class="skill-tag">Gestion</div>
                    <div class="skill-tag">Communication</div>
                </div>
            </div>
        </div>
        
        <main class="cv-main">
            <?php if (!empty($data['cvData']['summary'])): ?>
                <div class="main-header">
                    <h2><?= htmlspecialchars($data['cvData']['title'] ?? 'Profil Professionnel') ?></h2>
                    <div class="subtitle">CV optimisé par Intelligence Artificielle</div>
                </div>
                
                <div class="content-section">
                    <div class="section-title">Résumé Professionnel</div>
                    <div class="highlight-box">
                        <p><?= nl2br(htmlspecialchars($data['cvData']['summary'])) ?></p>
                    </div>
                </div>
                
                <?php if (!empty($data['cvData']['sections'])): ?>
                    <?php foreach ($data['cvData']['sections'] as $section): ?>
                        <div class="content-section">
                            <div class="section-title"><?= htmlspecialchars($section['title']) ?></div>
                            <ul class="section-items">
                                <?php foreach ($section['items'] as $item): ?>
                                    <li><?= htmlspecialchars($item) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="main-header">
                    <h2>Profil Professionnel</h2>
                    <div class="subtitle">CV optimisé par Intelligence Artificielle</div>
                </div>
                
                <div class="content-section">
                    <div class="section-title">CV Optimisé par Mistral IA</div>
                    <div class="highlight-box">
                        <div class="content-text">CV généré par IA - Contenu optimisé disponible dans les sections ci-dessus.</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
    return ob_get_clean();
}
?>