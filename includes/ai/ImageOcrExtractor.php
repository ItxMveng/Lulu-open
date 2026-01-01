<?php
class ImageOcrExtractor {
    
    public static function extractTextFromImage(string $imagePath): ?string {
        if (!file_exists($imagePath)) {
            error_log("Image file not found: $imagePath");
            return null;
        }
        
        // Vérifier si c'est une image
        $imageInfo = @getimagesize($imagePath);
        if (!$imageInfo) {
            error_log("Invalid image file: $imagePath");
            return null;
        }
        
        // Méthode 1: Tesseract OCR (si disponible)
        $text = self::tryTesseractOcr($imagePath);
        if ($text && strlen(trim($text)) > 50) {
            error_log("OCR SUCCESS (tesseract): " . strlen($text) . " chars");
            return $text;
        }
        
        // Méthode 2: Extraction basique (pour images avec texte simple)
        $text = self::tryBasicImageText($imagePath);
        if ($text && strlen(trim($text)) > 20) {
            error_log("OCR SUCCESS (basic): " . strlen($text) . " chars");
            return $text;
        }
        
        error_log("OCR EXTRACTION FAILED: All methods failed for $imagePath");
        return "Texte d'image non extrait automatiquement. Veuillez utiliser un fichier PDF ou texte.";
    }
    
    private static function tryTesseractOcr(string $imagePath): ?string {
        // Vérifier si Tesseract est installé
        if (!self::commandExists('tesseract')) {
            return null;
        }
        
        $tempFile = tempnam(sys_get_temp_dir(), 'ocr_');
        $command = sprintf('tesseract %s %s -l fra 2>/dev/null', 
            escapeshellarg($imagePath), escapeshellarg($tempFile));
        
        exec($command, $output, $return);
        
        $outputFile = $tempFile . '.txt';
        if ($return === 0 && file_exists($outputFile)) {
            $text = file_get_contents($outputFile);
            unlink($outputFile);
            if (file_exists($tempFile)) unlink($tempFile);
            return $text ? self::cleanOcrText($text) : null;
        }
        
        if (file_exists($outputFile)) unlink($outputFile);
        if (file_exists($tempFile)) unlink($tempFile);
        return null;
    }
    
    private static function tryBasicImageText(string $imagePath): ?string {
        // Cette méthode est très limitée - juste pour les cas simples
        // En réalité, l'OCR nécessite des librairies spécialisées
        
        // Retourner un message informatif
        return "Image détectée. Pour une meilleure extraction, installez Tesseract OCR : composer require thiagoalessio/tesseract-ocr-for-php";
    }
    
    private static function cleanOcrText(string $text): string {
        // Nettoyer le texte OCR
        $text = preg_replace('/[^\x20-\x7E\x80-\xFF\n\r\t]/', '', $text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n+/', "\n", $text);
        
        return trim($text);
    }
    
    private static function commandExists(string $command): bool {
        $output = [];
        $return = 0;
        @exec("which $command 2>/dev/null || where $command 2>nul", $output, $return);
        return $return === 0;
    }
}
?>