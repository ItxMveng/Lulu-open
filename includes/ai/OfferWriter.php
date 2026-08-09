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
        $system = <<<'PROMPT'
Tu es un expert en marque employeur et en rédaction d'offres d'emploi attractives EN FRANÇAIS.
À partir des éléments fournis, rédige une offre d'emploi complète, engageante et professionnelle.
Réponds UNIQUEMENT avec un objet JSON valide avec ces clés :
- "description" : chaîne (Markdown). Contient : une accroche sur l'entreprise et le poste, une section "## Vos missions" (5-6 puces concrètes avec verbes d'action), le contexte du poste.
- "profile_required" : chaîne (Markdown). Section "profil recherché" : compétences, expérience, savoir-être (puces).
- "benefits" : chaîne (Markdown). Ce que l'entreprise offre (rémunération si indiquée, avantages, ambiance, évolution).
- "keywords" : tableau de 6-10 mots-clés pertinents pour le référencement de l'offre.
Adapte au secteur et au type de contrat. Sois concret et attractif, jamais générique.
PROMPT;
        $user = "Titre du poste : {$title}\nSecteur : {$sector}\nCompétences clés : {$skillsText}\nType de contrat : {$contractType}\nÀ propos de l'entreprise : {$companyDescription}";
        $result = $this->provider->completeJson($system, $user, ['temperature' => 0.6]);

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