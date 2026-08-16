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
        $system = <<<'PROMPT'
Tu es un recruteur senior et expert en évaluation de candidatures. Tu analyses la correspondance entre un CV et une offre d'emploi.
Réponds UNIQUEMENT avec un objet JSON valide, en français, avec exactement ces clés :
- "match_score" : entier de 0 à 100 représentant l'adéquation réelle du profil avec l'offre (sois exigeant et honnête).
- "strengths" : tableau de 3 à 5 chaînes, chaque atout concret du candidat pour CE poste (compétences, expériences précises).
- "gaps" : tableau de 2 à 4 chaînes, les manques ou points de vigilance par rapport aux exigences de l'offre.
- "recommendation" : chaîne de 2 à 4 phrases, un conseil concret et actionnable pour le candidat afin d'améliorer sa candidature à cette offre.
Base-toi uniquement sur le contenu fourni. Sois précis, spécifique au domaine, jamais générique.
PROMPT;
        $user = "=== OFFRE ===\n" . $this->clip($offerText, 4000) . "\n\n=== CV DU CANDIDAT ===\n" . $this->clip($cvText, 4000);
        $result = $this->provider->completeJson($system, $user, ['temperature' => 0.3]);

        if (is_array($result) && isset($result['match_score'])) {
            return [
                'match_score' => max(0, min(100, (int) $result['match_score'])),
                'strengths' => array_values(array_filter(array_map('strval', (array) ($result['strengths'] ?? [])))),
                'gaps' => array_values(array_filter(array_map('strval', (array) ($result['gaps'] ?? [])))),
                'recommendation' => (string) ($result['recommendation'] ?? ''),
                'ai' => true,
            ];
        }

        return $this->fallback($cvText, $offerText) + ['ai' => false];
    }

    private function clip(string $text, int $max): string
    {
        $text = trim($text);
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) . '…' : $text;
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