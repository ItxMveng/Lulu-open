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

        $offerId = (int) ($_POST['offer_id'] ?? 0);
        $entrepriseId = (int) ($_POST['entreprise_id'] ?? 0);
        $offer = $this->offers->findById($offerId) ?? [];
        $coverLetter = trim((string) ($_POST['cover_letter'] ?? ''));

        $id = $this->applications->create([
            'applicant_id' => $userId,
            'entreprise_id' => $entrepriseId,
            'offer_id' => $offerId,
            'cv_path' => $cvPath,
            'cover_letter' => $coverLetter,
        ]);
        (new Activity())->log($userId, 'application_sent', ['application_id' => $id]);
        (new Notification())->create($entrepriseId, 'application_received', ['offer_id' => $offerId]);
        // L'analyse IA est calculée en arrière-plan côté recruteur (pas de blocage à la soumission).

        // Email de confirmation au candidat.
        $user = (new User())->findById($userId);
        if ($user && !empty($user['email'])) {
            AppMailer::applicationSent((string) $user['email'], (string) $user['name'], (string) ($offer['title'] ?? 'une offre'), '');
        }

        flash('Candidature envoyée au recruteur. Un email de confirmation vous a été adressé.', 'success');
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
            $bytes = DocumentRenderer::toDocx($generated, 'CV');
            file_put_contents(base_path($relative), $bytes);
            if (Storage::enabled()) {
                Storage::put($relative, $bytes, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            }
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
        $sort = (string) ($_GET['sort'] ?? 'score');
        $items = $this->applications->receivedByEntreprise((int) current_user_id(), $sort);
        $this->render('entreprise/applications/index', [
            'title' => 'Candidatures reçues',
            'applications' => $items,
            'sort' => $sort,
        ]);
    }

    public function updateStatus(string $id): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        verify_csrf();
        $status = (string) ($_POST['status'] ?? 'vue');

        $application = $this->applications->findForEntreprise((int) $id, (int) current_user_id());
        if (!$application) {
            flash('Candidature introuvable.', 'danger');
            redirect('/entreprise/candidatures');
        }

        // Entretien (fourni si statut entretien/acceptée).
        $interview = null;
        $rawDate = trim((string) ($_POST['interview_at'] ?? ''));
        if (in_array($status, ['entretien', 'acceptee'], true) && $rawDate !== '') {
            $interview = [
                'at' => $rawDate,
                'location' => trim((string) ($_POST['interview_location'] ?? '')),
                'note' => trim((string) ($_POST['interview_note'] ?? '')),
            ];
            $this->applications->saveInterview((int) $id, $interview['at'], $interview['location'], $interview['note']);
        }

        $this->applications->updateStatus((int) $id, $status);

        // Email personnalisé au candidat.
        if (!empty($application['candidate_email'])) {
            AppMailer::statusChanged(
                (string) $application['candidate_email'],
                (string) ($application['candidate_name'] ?? ''),
                (string) ($application['title'] ?? 'une offre'),
                $status,
                $interview
            );
        }
        (new Notification())->create((int) $application['applicant_id'], 'application_status', ['status' => $status]);

        flash('Statut mis à jour, le candidat a été notifié par email.', 'success');
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

        // Analyse en cache si disponible.
        if (!empty($application['analysis'])) {
            $cached = json_decode((string) $application['analysis'], true);
            if (is_array($cached)) {
                json_response($cached + ['cached' => true]);
            }
        }

        json_response($this->analyzeAndCache(
            (int) $id,
            (int) $application['applicant_id'],
            (string) ($application['offer_description'] ?? ''),
            (string) ($application['cover_letter'] ?? ''),
            (string) ($application['cv_path'] ?? '')
        ));
    }

    /** Analyse une candidature (profil + lettre + CV vs offre), met en cache le score et le résultat. */
    private function analyzeAndCache(int $appId, int $applicantId, string $offerDescription, string $coverLetter, string $cvPath): array
    {
        $analyzer = new CvAnalyzer();
        $profile = (new Profile())->getByUserId($applicantId) ?? [];
        $dec = static fn ($v): array => (is_array($v) ? $v : (json_decode((string) $v, true) ?: []));
        $profileText = implode("\n", array_filter([
            'Localisation : ' . (string) ($profile['location'] ?? ''),
            'Domaines : ' . implode(', ', $dec($profile['categories'] ?? '[]')),
            'Compétences : ' . implode(', ', $dec($profile['skills'] ?? '[]')),
            'Langues : ' . implode(', ', $dec($profile['languages'] ?? '[]')),
            'Présentation : ' . (string) ($profile['bio'] ?? ''),
        ]));

        $cvText = '';
        if ($cvPath !== '' && str_ends_with(strtolower($cvPath), '.pdf')) {
            $absolute = base_path($cvPath);
            if (is_file($absolute)) {
                $cvText = (string) $analyzer->extractTextFromPdf($absolute);
                if (str_contains($cvText, 'indisponible')) { $cvText = ''; }
            }
        }

        $fullCv = trim($profileText . "\n\n" . $cvText . "\n\nLettre de motivation :\n" . $coverLetter);
        $result = $analyzer->analyze($fullCv, $offerDescription);
        $this->applications->saveAnalysis($appId, (int) ($result['match_score'] ?? 0), $result);

        return $result;
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