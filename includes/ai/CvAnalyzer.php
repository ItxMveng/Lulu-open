<?php
declare(strict_types=1);

final class CvAnalyzer
{
    private IAProvider $provider;

    public function __construct()
    {
        $this->provider = new IAProvider();
    }

    public function analyze(string $cvText, string $offerText): array
    {
        $system = 'Return strict JSON with match_score, strengths, gaps and recommendation.';
        $user = 'CV: ' . $cvText . "\nOffre: " . $offerText;
        $result = $this->provider->completeJson($system, $user);

        if (is_array($result) && isset($result['match_score'])) {
            return [
                'match_score' => max(0, min(100, (int) $result['match_score'])),
                'strengths' => array_values((array) ($result['strengths'] ?? [])),
                'gaps' => array_values((array) ($result['gaps'] ?? [])),
                'recommendation' => (string) ($result['recommendation'] ?? 'Analyse IA réalisée.'),
            ];
        }

        return $this->fallback($cvText, $offerText);
    }

    public function extractTextFromPdf(string $filePath): string
    {
        $text = SimplePdfExtractor::extract($filePath);
        if ($text !== null && $text !== '') {
            return $text;
        }

        if (class_exists('Spatie\\PdfToText\\Pdf')) {
            try {
                return trim((string) \Spatie\PdfToText\Pdf::getText($filePath));
            } catch (Throwable) {
            }
        }

        return 'Extraction PDF indisponible.';
    }

    public function extractTextFromImage(string $filePath): string
    {
        return ImageOcrExtractor::extract($filePath) ?? 'Extraction OCR indisponible.';
    }

    private function fallback(string $cvText, string $offerText): array
    {
        $cvTokens = $this->tokenize($cvText);
        $offerTokens = $this->tokenize($offerText);
        $matches = array_values(array_intersect($cvTokens, $offerTokens));
        $missing = array_values(array_diff(array_slice($offerTokens, 0, 10), $cvTokens));
        $score = min(95, max(25, count($matches) * 8));

        return [
            'match_score' => $score,
            'strengths' => array_slice(array_unique($matches), 0, 5),
            'gaps' => array_slice(array_unique($missing), 0, 5),
            'recommendation' => 'Analyse locale effectuée faute de réponse IA disponible.',
        ];
    }

    private function tokenize(string $value): array
    {
        return array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($value)) ?: []));
    }
}