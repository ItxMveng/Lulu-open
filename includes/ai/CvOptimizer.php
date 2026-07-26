<?php
declare(strict_types=1);

final class CvOptimizer
{
    private IAProvider $provider;

    public function __construct()
    {
        $this->provider = new IAProvider();
    }

    public function optimize(string $cvText, string $offerText, string $targetRole): array
    {
        $system = 'Return strict JSON with summary, skills, experience_rewrite and keywords_to_add.';
        $user = "Rôle cible: {$targetRole}\nCV: {$cvText}\nOffre: {$offerText}";
        $result = $this->provider->completeJson($system, $user);

        if (is_array($result) && isset($result['summary'])) {
            return [
                'summary' => (string) $result['summary'],
                'skills' => array_values((array) ($result['skills'] ?? [])),
                'experience_rewrite' => array_values((array) ($result['experience_rewrite'] ?? [])),
                'keywords_to_add' => array_values((array) ($result['keywords_to_add'] ?? [])),
            ];
        }

        return [
            'summary' => mb_strimwidth(trim($cvText), 0, 180, '...'),
            'skills' => array_slice(array_values(array_unique(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($offerText)) ?: [])), 0, 8),
            'experience_rewrite' => ['Reformulez vos expériences avec des verbes d’action et des résultats mesurables.'],
            'keywords_to_add' => array_slice(array_values(array_unique(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($offerText)) ?: [])), 0, 10),
        ];
    }
}