<?php
declare(strict_types=1);

final class ImageOcrExtractor
{
    public static function extract(string $filePath): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }

        return null;
    }
}