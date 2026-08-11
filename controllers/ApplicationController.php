<?php
declare(strict_types=1);

final class ApplicationController extends Controller
{
    private Application $applications;
    private Offer $offers;

    public function __construct()
    {
        $this->applications = new Application();
        $this->offers = new Offer();
    }

    public function apply(string $offerId): void
    {
        AuthMiddleware::requireRole(['client']);
        $offer = $this->offers->findById((int) $offerId);
        if (!$offer) {
            abort(404, 'Offre introuvable.');
        }

        $this->render('client/applications/apply', [
            'title' => 'Postuler à une offre',
            'offer' => $offer,
            'cvDocuments' => (new CvDocument())->allForUser((int) current_user_id()),
        ]);
    }

    public function store(): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf();
        $userId = (int) current_user_id();

        $cvPath = $this->resolveApplicationCv($userId);
        if ($cvPath === null) {
            flash('Ajoutez un CV (enregistré, importé ou généré par l\'IA) pour postuler.', 'danger');
            redirect('/offres/' . (int) ($_POST['offer_id'] ?? 0) . '/postuler');
        }

        $id = $this->applications->create([
            'applicant_id' => $userId,
            'entreprise_id' => (int) ($_POST['entreprise_id'] ?? 0),
            'offer_id' => (int) ($_POST['offer_id'] ?? 0),
            'cv_path' => $cvPath,
            'cover_letter' => trim((string) ($_POST['cover_letter'] ?? '')),
        ]);
        (new Activity())->log($userId, 'application_sent', ['application_id' => $id]);
        (new Notification())->create((int) ($_POST['entreprise_id'] ?? 0), 'application_received', ['offer_id' => (int) ($_POST['offer_id'] ?? 0)]);
        flash('Candidature envoyée au recruteur. Bonne chance !', 'success');
        redirect('/client/candidatures');
    }

    /** Détermine le CV de la candidature : fichier importé, CV enregistré, ou CV généré par l'IA. */
    private function resolveApplicationCv(int $userId): ?string
    {
        if (!empty($_FILES['cv']['name'])) {
            return UploadHelper::storeUploadedFile($_FILES['cv'], 'cv', [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'image/jpeg',
                'image/png',
            ], 8 * 1024 * 1024);
        }

        $cvId = (int) ($_POST['cv_id'] ?? 0);
        if ($cvId > 0) {
            $cv = (new CvDocument())->find($cvId, $userId);
            if ($cv && !empty($cv['file_path'])) {
                return (string) $cv['file_path'];
            }
        }

        $generated = trim((string) ($_POST['generated_cv'] ?? ''));
        if ($generated !== '') {
            $dir = UPLOADS_PATH . DIRECTORY_SEPARATOR . 'cv';
            if (!is_dir($dir)) { mkdir($dir, 0775, true); }
            $relative = 'uploads/cv/cvia_' . bin2hex(random_bytes(8)) . '.docx';
            file_put_contents(base_path($relative), DocumentRenderer::toDocx($generated, 'CV'));
            return $relative;
        }

        return null;
    }

    public function index(): void
    {
        AuthMiddleware::requireRole(['client']);
        $items = $this->applications->sentByApplicant((int) current_user_id());
        $this->render('client/applications/sent', ['title' => 'Mes candidatures', 'applications' => $items]);
    }

    public function received(): void
    {
        AuthMiddleware::requireRole(['entreprise']);
        $items = $this->applications->receivedByEntreprise((int) current_user_id());
        $this->render('entreprise/applications/index', ['title' => 'Candidatures reçues', 'applications' => $items]);
    }

    public function updateStatus(string $id): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        verify_csrf();
        $this->applications->updateStatus((int) $id, (string) ($_POST['status'] ?? 'vue'));
        flash('Statut de la candidature mis à jour.', 'success');
        redirect('/entreprise/candidatures');
    }

    public function analyze(string $id): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        verify_csrf($_POST['_csrf_token'] ?? (json_decode((string) file_get_contents('php://input'), true)['_csrf_token'] ?? null));

        $application = $this->applications->findForEntreprise((int) $id, (int) current_user_id());
        if (!$application) {
            json_response(['error' => 'Candidature introuvable.'], 404);
        }

        $analyzer = new CvAnalyzer();

        // Profil du candidat comme base fiable (indépendant de l'extraction PDF).
        $dec = static fn ($v): array => (is_array($v) ? $v : (json_decode((string) $v, true) ?: []));
        $profileText = implode("\n", array_filter([
            'Candidat : ' . (string) ($application['candidate_name'] ?? ''),
            'Localisation : ' . (string) ($application['candidate_location'] ?? ''),
            'Domaines : ' . implode(', ', $dec($application['candidate_categories'] ?? '[]')),
            'Compétences : ' . implode(', ', $dec($application['candidate_skills'] ?? '[]')),
            'Langues : ' . implode(', ', $dec($application['candidate_languages'] ?? '[]')),
            'Présentation : ' . (string) ($application['candidate_bio'] ?? ''),
        ]));

        // Complément : texte du CV PDF si extractible.
        $cvText = '';
        if (!empty($application['cv_path'])) {
            $absolute = base_path((string) $application['cv_path']);
            if (is_file($absolute) && str_ends_with(strtolower($absolute), '.pdf')) {
                $cvText = (string) $analyzer->extractTextFromPdf($absolute);
                if (str_contains($cvText, 'indisponible')) { $cvText = ''; }
            }
        }

        $fullCv = trim($profileText . "\n\n" . $cvText . "\n\nLettre de motivation :\n" . (string) ($application['cover_letter'] ?? ''));

        json_response($analyzer->analyze($fullCv, (string) ($application['offer_description'] ?? '')));
    }

    public function destroy(string $id): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf();
        $this->applications->delete((int) $id, (int) current_user_id());
        flash('Candidature retirée.', 'success');
        redirect('/client/candidatures');
    }
}