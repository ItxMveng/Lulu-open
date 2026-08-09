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
        $entreprise = trim($entrepriseName) !== '' ? $entrepriseName : "l'entreprise";
        $system = <<<PROMPT
Tu es un expert en recherche d'emploi qui rédige des lettres de motivation percutantes EN FRANÇAIS.
Rédige une lettre de motivation personnalisée, prête à envoyer, sur un ton {$tone}.
Structure :
- Formule d'appel (Madame, Monsieur, ou au recruteur).
- Accroche : pourquoi ce poste et cette entreprise précisément (relie à l'offre).
- Corps : 1 à 2 paragraphes reliant CONCRÈTEMENT les compétences/expériences du candidat aux besoins de l'offre (cite des éléments réels du CV et de l'offre).
- Projection : ce que le candidat apportera.
- Formule de politesse et signature avec le nom du candidat.
Règles : 250 à 350 mots, spécifique (jamais de phrases creuses interchangeables), n'invente pas d'informations fausses. Pour les infos manquantes, utilise des crochets [comme ceci].
IMPORTANT : réponds en TEXTE BRUT uniquement, SANS AUCUN symbole de mise en forme Markdown (pas de **, pas de #, pas de *, pas de tirets de liste). Une lettre de motivation classique, paragraphes séparés par des sauts de ligne.
PROMPT;
        $user = "Candidat : {$userName}\nEntreprise : {$entreprise}\n\n=== OFFRE ===\n"
            . $this->clip($offerText, 3500) . "\n\n=== CV / PROFIL DU CANDIDAT ===\n" . $this->clip($cvText, 3500);
        $response = $this->provider->complete($system, $user, ['temperature' => 0.6]);

        if ($response !== null && trim($response) !== '') {
            return trim($response);
        }

        return "Madame, Monsieur,\n\nJe vous adresse ma candidature pour le poste proposé au sein de {$entreprise}. Mon parcours et mes compétences me permettent d'apporter une contribution concrète et rapide à vos équipes.\n\nJe serais heureux d'échanger avec vous afin de détailler ma motivation et la manière dont je peux répondre à vos besoins.\n\nCordialement,\n{$userName}";
    }

    private function clip(string $text, int $max): string
    {
        $text = trim($text);
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) . '…' : $text;
    }
}