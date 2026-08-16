<?php
declare(strict_types=1);

final class UploadHelper
{
    public static function storeUploadedFile(array $file, string $target, array $allowedMimes, int $maxBytes, ?string $oldRelativePath = null): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload invalide.');
        }

        $tmpName = $file['tmp_name'] ?? '';
        if (!is_string($tmpName) || !is_file($tmpName)) {
            throw new RuntimeException('Fichier temporaire introuvable.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('Le fichier dépasse la taille autorisée.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpName) ?: '';
        if (!in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException('Le type MIME du fichier est refusé.');
        }

        $extensionMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        ];
        $extension = $extensionMap[$mime] ?? pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION);
        $fileName = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . $extension : '');

        $directory = UPLOADS_PATH . DIRECTORY_SEPARATOR . trim($target, DIRECTORY_SEPARATOR);
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $destination = $directory . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            if (!rename($tmpName, $destination)) {
                throw new RuntimeException('Impossible de déplacer le fichier uploadé.');
            }
        }

        self::deleteRelativeFile($oldRelativePath);

        return 'uploads/' . trim($target, '/\\') . '/' . $fileName;
    }

    public static function deleteRelativeFile(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }

        $cleanPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $absolutePath = base_path($cleanPath);

        if (is_file($absolutePath) && str_starts_with($absolutePath, UPLOADS_PATH)) {
            unlink($absolutePath);
        }
    }
}