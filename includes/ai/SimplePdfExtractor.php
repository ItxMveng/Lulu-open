<?php
declare(strict_types=1);

final class SimplePdfExtractor
{
    public static function extract(string $filePath): ?string
    {
        if (!is_file($filePath)) {
            return null;
        }

        if (class_exists('Spatie\\PdfToText\\Pdf')) {
            try {
                return trim((string) \Spatie\PdfToText\Pdf::getText($filePath));
            } catch (Throwable) {
            }
        }

        return null;
    }
}