<?php
declare(strict_types=1);

final class ProfileEnhancer
{
    private IAProvider $provider;

    public function __construct()
    {
        $this->provider = new IAProvider();
    }

    public function suggest(array $profileData): array
    {
        $filledFields = 0;
        $trackedFields = ['display_name', 'bio', 'photo_path', 'location', 'categories', 'skills', 'languages', 'availability', 'portfolio'];

        foreach ($trackedFields as $field) {
            $value = $profileData[$field] ?? null;
            if ($value === null || $value === '' || $value === '[]') {
                continue;
            }
            $filledFields++;
        }

        $score = (int) round(($filledFields / count($trackedFields)) * 100);
        $suggestions = [];

        if (empty($profileData['photo_path'])) {
            $suggestions[] = 'Ajoutez une photo pour renforcer la confiance.';
        }
        if (empty($profileData['bio']) || mb_strlen((string) $profileData['bio']) < 120) {
            $suggestions[] = 'Votre bio est trop courte, détaillez votre valeur ajoutée.';
        }
        if (empty($profileData['skills']) || $profileData['skills'] === '[]') {
            $suggestions[] = 'Ajoutez des compétences clés pour améliorer votre visibilité.';
        }
        if (empty($profileData['portfolio']) || $profileData['portfolio'] === '[]') {
            $suggestions[] = 'Ajoutez des références ou des liens de portfolio.';
        }

        $rewrite = null;
        if (!empty($profileData['bio'])) {
            $json = $this->provider->completeJson('Return strict JSON with bio_rewrite.', 'Bio actuelle: ' . (string) $profileData['bio']);
            $rewrite = is_array($json) ? ($json['bio_rewrite'] ?? null) : null;
        }

        return [
            'completeness_score' => $score,
            'suggestions' => $suggestions,
            'bio_rewrite' => $rewrite ?: ($profileData['bio'] ?? null),
        ];
    }
}