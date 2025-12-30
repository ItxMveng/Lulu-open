<?php
/**
 * Script d'ajout du middleware require_client() dans toutes les vues CLIENT
 */

echo "<h1>🔐 Ajout middleware require_client()</h1>";

$files = glob('views/client/*.php');
$totalUpdates = 0;

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Vérifier si middleware déjà présent
    if (strpos($content, 'require_client()') !== false) {
        echo "<div>⏭️ Déjà protégé : " . basename($file) . "</div>";
        continue;
    }
    
    // Ajouter après le premier <?php
    $pattern = '/^<\?php\s*\n/';
    $replacement = "<?php\nrequire_once __DIR__ . '/../../includes/middleware.php';\nrequire_client();\n\n";
    
    $newContent = preg_replace($pattern, $replacement, $content, 1);
    
    if ($newContent !== $content) {
        file_put_contents($file, $newContent);
        $totalUpdates++;
        echo "<div>✅ Protégé : " . basename($file) . "</div>";
    } else {
        echo "<div>⚠️ Échec : " . basename($file) . "</div>";
    }
}

echo "<h2>🎉 $totalUpdates fichier(s) protégé(s) !</h2>";
?>
