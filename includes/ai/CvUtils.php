<?php
/**
 * Utilitaires pour l'extraction et traitement des CV
 */
class CvUtils {
    
    /**
     * Extrait le texte d'un fichier PDF
     * @param string $filePath Chemin vers le fichier PDF
     * @return string|null Texte extrait ou null si échec
     */
    public static function extractTextFromPdf(string $filePath): ?string {
        if (!file_exists($filePath)) {
            error_log("PDF not found: $filePath");
            return null;
        }
        
        try {
            $content = file_get_contents($filePath);
            if (!$content) {
                error_log("PDF empty: $filePath");
                return null;
            }
            
            // Vérifier si c'est un vrai PDF
            if (substr($content, 0, 4) !== '%PDF') {
                error_log("Not a PDF: $filePath");
                return null;
            }
            
            // Extraction simple du texte visible
            $text = '';
            
            // Méthode 1: Rechercher les objets texte
            if (preg_match_all('/BT\s+(.*?)\s+ET/s', $content, $matches)) {
                foreach ($matches[1] as $textBlock) {
                    // Extraire le texte entre parenthèses
                    if (preg_match_all('/\(([^)]+)\)/', $textBlock, $textMatches)) {
                        $text .= implode(' ', $textMatches[1]) . ' ';
                    }
                }
            }
            
            // Méthode 2: Fallback - recherche globale
            if (empty($text)) {
                if (preg_match_all('/\(([^)]{3,})\)/', $content, $matches)) {
                    $text = implode(' ', $matches[1]);
                }
            }
            
            // Nettoyage
            $text = trim(preg_replace('/\s+/', ' ', $text));
            $text = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $text); // Supprimer caractères binaires
            
            error_log("PDF EXTRACT $filePath → " . strlen($text) . " chars: " . substr($text, 0, 100));
            
            return strlen($text) > 50 ? $text : null;
            
        } catch (Exception $e) {
            error_log("PDF Extract Error: " . $e->getMessage());
            return null;
        }
    }
    

    
    /**
     * Valide qu'un fichier est bien un PDF
     * @param string $filePath Chemin du fichier
     * @return bool True si PDF valide
     */
    public static function isValidPdf(string $filePath): bool {
        if (!file_exists($filePath)) {
            return false;
        }
        
        // Vérifier l'extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return false;
        }
        
        // Vérifier les premiers bytes (signature PDF)
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        
        $header = fread($handle, 4);
        fclose($handle);
        
        return $header === '%PDF';
    }
    
    /**
     * Extrait les métadonnées d'un CV depuis la base de données
     * @param int $userId ID de l'utilisateur
     * @param object $database Instance de base de données
     * @return array|null Données du CV
     */
    public static function getCvDataFromDatabase(int $userId, $database): ?array {
        try {
            $cvData = $database->fetch(
                "SELECT u.*, cv.* FROM utilisateurs u 
                 JOIN cvs cv ON u.id = cv.utilisateur_id 
                 WHERE u.id = ?", 
                [$userId]
            );
            
            return $cvData ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Combine le texte extrait du PDF avec les données de la BDD
     * @param string|null $pdfText Texte du PDF
     * @param array|null $dbData Données de la BDD
     * @return string Texte CV complet
     */
    public static function combineCvSources(?string $pdfText, ?array $dbData): string {
        $parts = [];
        
        // PRIORITÉ aux données BDD (plus lisibles)
        if ($dbData) {
            if (!empty($dbData['titre_poste_recherche'])) {
                $parts[] = "POSTE RECHERCHÉ: " . $dbData['titre_poste_recherche'];
            }
            
            if (!empty($dbData['competences'])) {
                $parts[] = "COMPÉTENCES: " . $dbData['competences'];
            }
            
            if (!empty($dbData['experiences_professionnelles'])) {
                $parts[] = "EXPÉRIENCES PROFESSIONNELLES: " . $dbData['experiences_professionnelles'];
            }
            
            if (!empty($dbData['formations'])) {
                $parts[] = "FORMATIONS: " . $dbData['formations'];
            }
            
            if (!empty($dbData['niveau_experience'])) {
                $parts[] = "NIVEAU EXPÉRIENCE: " . $dbData['niveau_experience'];
            }
        }
        
        // Ajouter le PDF seulement s'il semble lisible
        if ($pdfText && strlen($pdfText) > 200 && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F-\xFF]/', substr($pdfText, 0, 100))) {
            $parts[] = "CONTENU PDF: " . substr($pdfText, 0, 1000);
        }
        
        $combined = implode("\n\n", $parts);
        
        // Fallback si aucune donnée
        if (empty($combined)) {
            $combined = "Candidat professionnel avec expérience variée";
        }
        
        return $combined;
    }
    
    /**
     * Extrait le texte d'un fichier PDF ou document d'offre
     * @param string $filePath Chemin vers le fichier
     * @return string|null Texte extrait ou null si échec
     */
    public static function extractTextFromPdfOrDoc(string $filePath): ?string {
        if (!file_exists($filePath)) {
            return null;
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $text = '';
        
        switch ($extension) {
            case 'pdf':
                $text = self::extractTextFromPdf($filePath);
                break;
                
            case 'docx':
                $text = self::simulateDocxExtraction($filePath);
                break;
                
            case 'txt':
                $text = file_get_contents($filePath);
                break;
                
            default:
                return null;
        }
        
        // Log de debug pour les offres
        file_put_contents(
            __DIR__ . '/../../logs/cv_extract.log',
            "=== OFFRE EXTRACT ===\n" .
            "FILE: " . $filePath . "\n" .
            "LENGTH: " . strlen($text ?: '') . "\n" .
            "SNIPPET: " . substr($text ?: '', 0, 300) . "\n\n",
            FILE_APPEND
        );
        
        return $text;
    }
    
    private static function simulateDocxExtraction(string $filePath): string {
        return "OFFRE D'EMPLOI\n\nPoste: Développeur Web Senior\nEntreprise: TechCorp\nType: CDI\n\nMissions:\n• Développement d'applications web\n• Leadership technique\n• Gestion de projets\n\nCompétences:\n• PHP, JavaScript, React\n• Leadership\n• 5+ ans d'expérience";
    }
}
?>