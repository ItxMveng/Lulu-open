<?php
declare(strict_types=1);

/**
 * Extrait le texte d'un document uploadé : PDF (pdftotext) ou image (vision IA Mistral).
 */
final class DocumentExtractor
{
    public static function extractFromUpload(array $file): ?string
    {
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_file($tmp)) {
            return null;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string) ($finfo->file($tmp) ?: '');

        if ($mime === 'application/pdf') {
            $text = SimplePdfExtractor::extract($tmp);
            return ($text !== null && trim($text) !== '') ? trim($text) : null;
        }

        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return self::extractFromImage($tmp, $mime);
        }

        return null;
    }

    public static function extractFromImage(string $path, string $mime): ?string
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            return null;
        }
        $dataUri = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        $text = (new IAProvider())->visionExtract(
            $dataUri,
            "Transcris fidèlement tout le texte lisible de cette image (il s'agit probablement d'une offre d'emploi ou d'un document professionnel). Réponds uniquement avec le texte, sans commentaire."
        );

        return ($text !== null && trim($text) !== '') ? trim($text) : null;
    }
}
