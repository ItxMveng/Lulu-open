<?php
declare(strict_types=1);

/**
 * Optimisation d'image à l'upload : on redimensionne fortement et on recompresse
 * (WebP si disponible, sinon JPEG) pour garder des fichiers minuscules
 * (~50–100 Ko), puis on stocke sur R2 (persistant) via Storage.
 *
 * Objectif : permettre un visuel par prestation sans faire exploser le stockage.
 */
final class ImageOptimizer
{
    private const MAX_DIM = 900;          // côté max en pixels
    private const MAX_SOURCE_BYTES = 8 * 1024 * 1024; // 8 Mo en entrée

    /**
     * Traite un fichier uploadé ($_FILES['x']) et retourne le chemin relatif
     * (ex. "uploads/services/ab12.webp") ou null si invalide.
     */
    public static function processUpload(array $file, string $target = 'services'): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        $tmp = $file['tmp_name'] ?? '';
        if (!is_string($tmp) || !is_file($tmp) || (int) ($file['size'] ?? 0) > self::MAX_SOURCE_BYTES) {
            return null;
        }

        $info = @getimagesize($tmp);
        if ($info === false) {
            return null;
        }
        [$width, $height] = $info;
        $mime = $info['mime'] ?? '';

        $src = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png'  => @imagecreatefrompng($tmp),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            'image/gif'  => @imagecreatefromgif($tmp),
            default      => false,
        };
        if (!$src) {
            return null;
        }

        // Calcul des dimensions cibles (jamais d'agrandissement).
        $ratio = min(1, self::MAX_DIM / max($width, $height));
        $newW = max(1, (int) round($width * $ratio));
        $newH = max(1, (int) round($height * $ratio));

        $dst = imagecreatetruecolor($newW, $newH);
        // Fond blanc (aplati la transparence pour le JPEG).
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $newW, $newH, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($src);

        // Encodage : WebP si supporté (meilleure compression), sinon JPEG.
        $useWebp = function_exists('imagewebp');
        $ext = $useWebp ? 'webp' : 'jpg';
        $rel = 'uploads/' . trim($target, '/\\') . '/svc_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $abs = base_path($rel);
        $dir = dirname($abs);
        if (!is_dir($dir)) { mkdir($dir, 0775, true); }

        ob_start();
        if ($useWebp) {
            imagewebp($dst, null, 80);
        } else {
            imagejpeg($dst, null, 82);
        }
        $bytes = (string) ob_get_clean();
        imagedestroy($dst);

        if ($bytes === '') {
            return null;
        }
        file_put_contents($abs, $bytes);

        if (Storage::enabled()) {
            Storage::put($rel, $bytes, $useWebp ? 'image/webp' : 'image/jpeg');
        }

        return $rel;
    }
}
