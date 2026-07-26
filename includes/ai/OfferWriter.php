<?php
declare(strict_types=1);

final class OfferWriter
{
    private IAProvider $provider;

    public function __construct()
    {
        $this->provider = new IAProvider();
    }

    public function generate(string $title, string $sector, array|string $skills, string $contractType, string $companyDescription): array
    {
        $skillsText = is_array($skills) ? implode(', ', $skills) : $skills;
        $system = 'Return strict JSON with title, description, profile_required, benefits and keywords.';
        $user = "Titre: {$title}\nSecteur: {$sector}\nCompétences: {$skillsText}\nContrat: {$contractType}\nEntreprise: {$companyDescription}";
        $result = $this->provider->completeJson($system, $user);

        if (is_array($result) && isset($result['description'])) {
            return $result;
        }

        return [
            'title' => $title,
            'description' => "Nous recherchons un profil {$title} pour accompagner notre activité dans le secteur {$sector}.",
            'profile_required' => "Vous maîtrisez {$skillsText} et savez évoluer dans un environnement {$sector}.",
            'benefits' => 'Cadre de travail collaboratif, montée en compétences et missions stimulantes.',
            'keywords' => array_values(array_filter(array_map('trim', explode(',', $skillsText)))),
        ];
    }
}