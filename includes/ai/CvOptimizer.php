<?php
declare(strict_types=1);

final class CvOptimizer
{
    private IAProvider $provider;

    public function __construct()
    {
        $this->provider = new IAProvider();
    }

    /**
     * Génère un CV complet, structuré et prêt à l'emploi, à partir des informations
     * du candidat, en le ciblant sur l'offre visée le cas échéant.
     * Retourne : ['cv' => string(markdown), 'keywords' => string[], 'ai' => bool]
     */
    public function optimize(string $sourceText, string $offerText, string $targetRole): array
    {
        $system = <<<'PROMPT'
Tu es un expert en rédaction de CV et en recrutement. À partir des informations fournies sur un candidat, tu rédiges un CV professionnel, clair et percutant, EN FRANÇAIS, au format Markdown.
Structure attendue :
# Prénom Nom
*Titre du poste visé*

## Profil
(3-4 phrases d'accroche percutantes, orientées résultats)

## Compétences
(liste à puces, regroupées, pertinentes pour le poste visé)

## Expériences professionnelles
(pour chaque expérience : **Poste — Entreprise** *(période si connue)*, puis 2-3 puces avec des réalisations concrètes et des verbes d'action ; si aucune expérience n'est fournie, propose une trame réaliste à compléter, indiquée entre crochets)

## Formation
(diplômes/formations si fournis, sinon [À compléter])

## Langues
(si fournies)

Règles : n'invente jamais de fausses informations vérifiables (entreprises, diplômes précis) ; pour les éléments manquants utilise des espaces réservés entre crochets comme [Nom de l'entreprise]. Optimise le vocabulaire avec les mots-clés du poste visé. Réponds uniquement avec le Markdown du CV, sans commentaire.
PROMPT;
        $target = $targetRole !== '' ? $targetRole : 'le poste visé';
        $user = "Poste visé : {$target}\n\n=== INFORMATIONS DU CANDIDAT ===\n" . $this->clip($sourceText, 5000);
        if (trim($offerText) !== '') {
            $user .= "\n\n=== OFFRE CIBLÉE (pour adapter le vocabulaire) ===\n" . $this->clip($offerText, 3000);
        }

        $cv = $this->provider->complete($system, $user, ['temperature' => 0.4]);

        if ($cv !== null && trim($cv) !== '') {
            $cv = trim($cv);
            // Retire un éventuel bloc de code Markdown englobant (```markdown ... ```).
            $cv = preg_replace('/^```[a-zA-Z]*\s*\n?/', '', $cv);
            $cv = preg_replace('/\n?```\s*$/', '', $cv);
            return ['cv' => trim($cv), 'keywords' => $this->extractKeywords($offerText), 'ai' => true];
        }

        // Fallback minimal sans IA.
        return [
            'cv' => "# " . '[Votre nom]' . "\n*{$target}*\n\n## Profil\n[Décrivez votre parcours en quelques phrases]\n\n## Compétences\n" . $this->clip($sourceText, 500),
            'keywords' => $this->extractKeywords($offerText),
            'ai' => false,
        ];
    }

    private function extractKeywords(string $text): array
    {
        $tokens = array_values(array_unique(preg_split('/[^\p{L}\p{N}+#.]+/u', mb_strtolower($text)) ?: []));
        $tokens = array_filter($tokens, static fn ($t): bool => mb_strlen($t) > 3);
        return array_slice(array_values($tokens), 0, 12);
    }

    private function clip(string $text, int $max): string
    {
        $text = trim($text);
        return mb_strlen($text) > $max ? mb_substr($text, 0, $max) . '…' : $text;
    }
}
