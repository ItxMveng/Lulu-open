<?php
/**
 * Extracteur PDF simple et efficace
 */
class SimplePdfExtractor {
    
    public static function extractText(string $filePath): ?string {
        if (!file_exists($filePath)) {
            return null;
        }
        
        try {
            $content = file_get_contents($filePath);
            if (!$content || substr($content, 0, 4) !== '%PDF') {
                return null;
            }
            
            // Méthode 1: Rechercher les objets texte avec Tj
            $text = '';
            if (preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches)) {
                $text = implode(' ', $matches[1]);
            }
            
            // Méthode 2: Rechercher les chaînes entre parenthèses
            if (empty($text)) {
                if (preg_match_all('/\(([^)]{5,})\)/', $content, $matches)) {
                    $text = implode(' ', $matches[1]);
                }
            }
            
            // Méthode 3: Rechercher dans les streams décompressés
            if (empty($text)) {
                if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $streamMatches)) {
                    foreach ($streamMatches[1] as $stream) {
                        $decoded = @gzuncompress($stream);
                        if ($decoded && preg_match_all('/\(([^)]+)\)/', $decoded, $textMatches)) {
                            $text .= implode(' ', $textMatches[1]) . ' ';
                        }
                    }
                }
            }
            
            // Nettoyage final
            $text = preg_replace('/\\\\[nrt]/', ' ', $text); // Échappements
            $text = preg_replace('/[^\x20-\x7E\xC0-\xFF]/', '', $text); // Caractères non-imprimables
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            return strlen($text) > 50 ? $text : null;
            
        } catch (Exception $e) {
            return null;
        }
    }
}
?>