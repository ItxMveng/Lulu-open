<?php
declare(strict_types=1);

final class CoverLetterGenerator
{
    private IAProvider $provider;

    public function __construct()
    {
        $this->provider = new IAProvider();
    }

    public function generate(string $cvText, string $offerText, string $userName, string $entrepriseName, string $tone): string
    {
        $system = 'Write a concise French cover letter.';
        $user = "Candidat: {$userName}\nEntreprise: {$entrepriseName}\nTon: {$tone}\nCV: {$cvText}\nOffre: {$offerText}";
        $response = $this->provider->complete($system, $user);

        if ($response === null || trim($response) === '') {
            $response = $this->provider->complete('Write a short French cover letter.', "{$userName} postule chez {$entrepriseName}. Ton {$tone}. Offre: {$offerText}");
        }

        if ($response !== null && trim($response) !== '') {
            return trim($response);
        }

        return "Madame, Monsieur,\n\nJe vous adresse ma candidature avec un fort intérêt pour cette opportunité chez {$entrepriseName}. Mon parcours et mes compétences me permettent d'apporter une contribution concrète et rapide à vos équipes.\n\nJe serais heureux d'échanger avec vous afin de détailler ma motivation et la manière dont je peux répondre à vos besoins.\n\nCordialement,\n{$userName}";
    }
}