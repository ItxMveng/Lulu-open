<?php
/**
 * SITEMAP.XML - LULU-OPEN
 * Génération automatique du sitemap
 */

require_once 'config/config.php';

header('Content-Type: application/xml; charset=utf-8');

$base_url = rtrim(APP_URL, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Pages statiques
$pages = [
    ['loc' => '', 'priority' => '1.0', 'changefreq' => 'daily'], // Accueil
    ['loc' => 'services.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => 'emplois.php', 'priority' => '0.9', 'changefreq' => 'daily'],
    ['loc' => 'about', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => 'contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
    ['loc' => 'cgu', 'priority' => '0.5', 'changefreq' => 'yearly'],
    ['loc' => 'privacy', 'priority' => '0.5', 'changefreq' => 'yearly'],
    ['loc' => 'login.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
    ['loc' => 'register.php', 'priority' => '0.6', 'changefreq' => 'monthly'],
];

foreach ($pages as $page) {
    echo "  <url>\n";
    echo "    <loc>{$base_url}/{$page['loc']}</loc>\n";
    echo "    <changefreq>{$page['changefreq']}</changefreq>\n";
    echo "    <priority>{$page['priority']}</priority>\n";
    echo "  </url>\n";
}

// Pages dynamiques (profils prestataires)
try {
    global $database;
    $pdo = $database->getConnection();
    
    // Prestataires actifs (limité à 500 pour performance)
    $stmt = $pdo->query("
        SELECT pp.id, pp.date_modification 
        FROM profils_prestataires pp
        JOIN utilisateurs u ON pp.utilisateur_id = u.id
        WHERE u.statut = 'actif'
        ORDER BY pp.date_modification DESC
        LIMIT 500
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = date('Y-m-d', strtotime($row['date_modification']));
        echo "  <url>\n";
        echo "    <loc>{$base_url}/profile-detail.php?id={$row['id']}</loc>\n";
        echo "    <lastmod>$lastmod</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
    }
    
    // CVs visibles (limité à 500)
    $stmt = $pdo->query("
        SELECT c.id, c.date_modification 
        FROM cvs c
        JOIN utilisateurs u ON c.utilisateur_id = u.id
        WHERE c.statut_visible = 1 AND u.statut = 'actif'
        ORDER BY c.date_modification DESC
        LIMIT 500
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastmod = date('Y-m-d', strtotime($row['date_modification']));
        echo "  <url>\n";
        echo "    <loc>{$base_url}/view-cv.php?id={$row['id']}</loc>\n";
        echo "    <lastmod>$lastmod</lastmod>\n";
        echo "    <changefreq>weekly</changefreq>\n";
        echo "    <priority>0.8</priority>\n";
        echo "  </url>\n";
    }
    
} catch (Exception $e) {
    // Erreur silencieuse - le sitemap continue avec les pages statiques
    error_log("Erreur génération sitemap: " . $e->getMessage());
}

echo '</urlset>';
?>
