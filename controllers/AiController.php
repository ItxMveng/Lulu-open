<?php
declare(strict_types=1);

/**
 * Outils IA côté candidat (rôle client) : analyse de CV, optimisation de CV,
 * génération de lettre de motivation. S'appuie sur les classes includes/ai/*,
 * qui possèdent chacune un fallback si l'IA (Mistral) n'est pas disponible.
 */
final class AiController extends Controller
{
    public function tools(): void
    {
        AuthMiddleware::requireRole(['client']);
        $this->render('client/ia-tools', [
            'title' => 'Outils IA',
            'cvDocuments' => (new CvDocument())->allForUser((int) current_user_id()),
            'aiConfigured' => (string) env('MISTRAL_API_KEY', '') !== '',
        ]);
    }

    public function analyzeCv(): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf($this->input('_csrf_token'));
        $cvText = $this->resolveCvText();
        $offerText = trim((string) $this->input('offer_text'));

        if ($cvText === '' && $offerText === '') {
            json_response(['error' => 'Fournissez un CV et/ou une offre.'], 422);
        }

        json_response((new CvAnalyzer())->analyze($cvText, $offerText));
    }

    public function optimizeCv(): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf($this->input('_csrf_token'));
        $cvText = $this->resolveCvText();
        $offerText = trim((string) $this->input('offer_text'));
        $targetRole = trim((string) $this->input('target_role')) ?: 'candidat';

        json_response((new CvOptimizer())->optimize($cvText, $offerText, $targetRole));
    }

    public function coverLetter(): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf($this->input('_csrf_token'));
        $cvText = $this->resolveCvText();
        $offerText = trim((string) $this->input('offer_text'));
        $entreprise = trim((string) $this->input('entreprise')) ?: 'l\'entreprise';
        $tone = trim((string) $this->input('tone')) ?: 'professionnel';
        $userName = (string) (auth_user()['name'] ?? 'Candidat');

        $text = (new CoverLetterGenerator())->generate($cvText, $offerText, $userName, $entreprise, $tone);
        json_response(['text' => $text]);
    }

    /**
     * Importe une offre depuis un lien, un fichier (PDF) ou une image (OCR IA).
     * Multipart : utilise $_POST/$_FILES (pas de JSON body pour l'upload).
     */
    public function importOffer(): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf($_POST['_csrf_token'] ?? null);

        $type = (string) ($_POST['source_type'] ?? 'text');
        $text = null;

        if ($type === 'url') {
            $text = LinkExtractor::fetch((string) ($_POST['url'] ?? ''));
        } elseif ($type === 'file' && !empty($_FILES['document']['tmp_name'])) {
            $text = DocumentExtractor::extractFromUpload($_FILES['document']);
        } else {
            $text = trim((string) ($_POST['text'] ?? ''));
        }

        if ($text === null || trim($text) === '') {
            json_response(['error' => "Impossible de récupérer le contenu (le site bloque peut-être l'accès automatique). Copiez-collez le texte de l'offre à la place."], 422);
        }

        // Raffinage IA : extraire l'offre propre à partir du texte brut récupéré.
        $clean = $this->refineOffer(trim($text), $type);
        json_response(['text' => $clean, 'refined' => $clean !== trim($text)]);
    }

    private function refineOffer(string $raw, string $type): string
    {
        $ai = new IAProvider();
        // On ne raffine que le contenu brut issu d'un lien/fichier (le texte collé est déjà propre).
        if (!$ai->enabled() || $type === 'text' || mb_strlen($raw) < 200) {
            return $raw;
        }
        $system = "Tu extrais le contenu utile d'une offre d'emploi à partir d'un texte brut (souvent issu d'une page web avec du bruit : menus, cookies, pieds de page). "
            . "Renvoie UNIQUEMENT le contenu de l'offre en texte clair et structuré (intitulé, entreprise, missions, profil recherché, conditions), en français, sans le bruit. Si aucune offre n'est identifiable, renvoie le texte tel quel nettoyé.";
        $refined = $ai->complete($system, mb_substr($raw, 0, 8000), ['temperature' => 0.2]);
        return ($refined !== null && trim($refined) !== '') ? trim($refined) : $raw;
    }

    /** Génère un CV structuré à partir du profil du candidat (+ poste ciblé). */
    public function generateCv(): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf($this->input('_csrf_token'));

        $profile = (new Profile())->getByUserId((int) current_user_id()) ?? [];
        $dec = static fn ($v): array => (is_array($v) ? $v : (json_decode((string) $v, true) ?: []));
        $profileText = implode("\n", array_filter([
            'Nom: ' . (string) (auth_user()['name'] ?? ''),
            'Titre/domaine: ' . implode(', ', $dec($profile['categories'] ?? '[]')),
            'Compétences: ' . implode(', ', $dec($profile['skills'] ?? '[]')),
            'Langues: ' . implode(', ', $dec($profile['languages'] ?? '[]')),
            'Certifications: ' . implode(', ', $dec($profile['certifications'] ?? '[]')),
            'Bio: ' . (string) ($profile['bio'] ?? ''),
        ]));
        $targetRole = trim((string) $this->input('target_role')) ?: 'candidat';
        $offerText = trim((string) $this->input('offer_text'));

        $result = (new CvOptimizer())->optimize($profileText, $offerText, $targetRole);
        json_response($result);
    }

    /** Récupère la valeur postée (JSON body ou form). */
    private function input(string $key): mixed
    {
        static $body = null;
        if ($body === null) {
            $raw = (string) file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : [];
        }
        return $body[$key] ?? $_POST[$key] ?? null;
    }

    /** Texte du CV : soit le CV sélectionné (extraction PDF), soit du texte collé. */
    private function resolveCvText(): string
    {
        $cvId = (int) ($this->input('cv_id') ?? 0);
        if ($cvId > 0) {
            $cv = (new CvDocument())->find($cvId, (int) current_user_id());
            if ($cv && !empty($cv['file_path'])) {
                $absolute = base_path((string) $cv['file_path']);
                if (is_file($absolute)) {
                    return (new CvAnalyzer())->extractTextFromPdf($absolute);
                }
            }
        }
        return trim((string) ($this->input('cv_text') ?? ''));
    }
}
