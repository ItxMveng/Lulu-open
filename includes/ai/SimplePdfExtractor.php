<?php
class SimplePdfExtractor {
    
    public static function extractText(string $filePath): ?string {
        if (!file_exists($filePath)) {
            error_log("PDF file not found: $filePath");
            return null;
        }
        
        // Méthode 1: pdftotext (le plus fiable)
        $text = self::tryPdfToText($filePath);
        if ($text && strlen(trim($text)) > 50) {
            error_log("PDF SUCCESS (pdftotext): " . strlen($text) . " chars");
            return $text;
        }
        
        // Méthode 2: Spatie PDF-to-Text
        $text = self::trySpatiePdf($filePath);
        if ($text && strlen(trim($text)) > 50) {
            error_log("PDF SUCCESS (spatie): " . strlen($text) . " chars");
            return $text;
        }
        
        // Méthode 3: Extraction PHP native
        $text = self::tryNativeExtraction($filePath);
        if ($text && strlen(trim($text)) > 50) {
            error_log("PDF SUCCESS (native): " . strlen($text) . " chars");
            return $text;
        }
        
        // Méthode 4: Extraction basique (dernière chance)
        $text = self::tryBasicExtraction($filePath);
        if ($text && strlen(trim($text)) > 30) {
            error_log("PDF SUCCESS (basic): " . strlen($text) . " chars");
            return $text;
        }
        
        error_log("PDF EXTRACTION FAILED: All methods failed for $filePath");
        return null;
    }
    
    private static function tryPdfToText(string $filePath): ?string {
        if (!self::commandExists('pdftotext')) return null;
        
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_');
        $command = sprintf('pdftotext -layout -enc UTF-8 %s %s 2>/dev/null', 
            escapeshellarg($filePath), escapeshellarg($tempFile));
        
        exec($command, $output, $return);
        
        if ($return === 0 && file_exists($tempFile)) {
            $text = file_get_contents($tempFile);
            unlink($tempFile);
            return $text ? self::cleanText($text) : null;
        }
        
        if (file_exists($tempFile)) unlink($tempFile);
        return null;
    }
    
    private static function trySpatiePdf(string $filePath): ?string {
        if (!class_exists('Spatie\\PdfToText\\Pdf')) return null;
        
        try {
            $text = \Spatie\PdfToText\Pdf::getText($filePath);
            return $text ? self::cleanText($text) : null;
        } catch (Exception $e) {
            error_log("Spatie PDF error: " . $e->getMessage());
            return null;
        }
    }
    
    private static function tryNativeExtraction(string $filePath): ?string {
        try {
            $content = file_get_contents($filePath);
            if (!$content) return null;
            
            $text = '';
            
            // Extraire les streams
            if (preg_match_all('/stream\s*\n(.*?)\nendstream/s', $content, $matches)) {
                foreach ($matches[1] as $stream) {
                    $decoded = @gzuncompress($stream);
                    if ($decoded === false) {
                        $decoded = @gzinflate($stream);
                    }
                    if ($decoded === false) {
                        $decoded = $stream;
                    }
                    
                    $text .= self::extractFromStream($decoded);
                }
            }
            
            // Extraction directe si pas de streams
            if (strlen($text) < 100) {
                $text .= self::extractFromStream($content);
            }
            
            return self::cleanText($text);
            
        } catch (Exception $e) {
            error_log("Native extraction error: " . $e->getMessage());
            return null;
        }
    }
    
    private static function tryBasicExtraction(string $filePath): ?string {
        try {
            $content = file_get_contents($filePath);
            if (!$content) return null;
            
            // Extraction très basique - chercher du texte lisible
            $text = '';
            
            // Méthode 1: Regex pour texte entre parenthèses
            if (preg_match_all('/\(([^)]{2,})\)/', $content, $matches)) {
                foreach ($matches[1] as $match) {
                    if (preg_match('/[a-zA-Z]{2,}/', $match)) {
                        $text .= $match . ' ';
                    }
                }
            }
            
            // Méthode 2: Chercher des mots dans le contenu brut
            if (strlen($text) < 50) {
                if (preg_match_all('/[a-zA-Z]{3,}/', $content, $matches)) {
                    $words = array_unique($matches[0]);
                    $text = implode(' ', array_slice($words, 0, 100));
                }
            }
            
            return self::cleanText($text);
            
        } catch (Exception $e) {
            error_log("Basic extraction error: " . $e->getMessage());
            return null;
        }
    }
    
    private static function extractFromStream(string $data): string {
        $text = '';
        
        // Texte entre parenthèses avec opérateurs Tj
        if (preg_match_all('/\(([^)]+)\)\s*T[jJ]/', $data, $matches)) {
            foreach ($matches[1] as $match) {
                if (strlen($match) > 1) {
                    $text .= $match . ' ';
                }
            }
        }
        
        // Tableaux de texte
        if (preg_match_all('/\[([^\]]+)\]\s*TJ/', $data, $matches)) {
            foreach ($matches[1] as $array) {
                if (preg_match_all('/\(([^)]+)\)/', $array, $subMatches)) {
                    $text .= implode('', $subMatches[1]) . ' ';
                }
            }
        }
        
        // Texte simple entre parenthèses
        if (strlen($text) < 50) {
            if (preg_match_all('/\(([^)]{3,})\)/', $data, $matches)) {
                foreach ($matches[1] as $match) {
                    if (preg_match('/[a-zA-Z]/', $match)) {
                        $text .= $match . ' ';
                    }
                }
            }
        }
        
        return $text;
    }
    
    private static function cleanText(string $text): string {
        // Décoder les caractères octaux
        $text = preg_replace_callback('/\\\\([0-7]{3})/', function($m) {
            return chr(octdec($m[1]));
        }, $text);
        
        // Nettoyer les échappements
        $text = str_replace(['\\(', '\\)', '\\n', '\\r', '\\t'], ['(', ')', "\n", "\n", ' '], $text);
        
        // Supprimer caractères de contrôle
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);
        
        // Normaliser les espaces
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