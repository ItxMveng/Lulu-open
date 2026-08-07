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
