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

        $this->render('client/applications/apply', ['title' => 'Postuler à une offre', 'offer' => $offer]);
    }

    public function store(): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf();
        $cvPath = !empty($_FILES['cv']['name']) ? UploadHelper::storeUploadedFile($_FILES['cv'], 'cv', ['application/pdf'], 5 * 1024 * 1024) : null;
        $id = $this->applications->create([
            'applicant_id' => (int) current_user_id(),
            'entreprise_id' => (int) ($_POST['entreprise_id'] ?? 0),
            'offer_id' => (int) ($_POST['offer_id'] ?? 0),
            'cv_path' => $cvPath,
            'cover_letter' => trim((string) ($_POST['cover_letter'] ?? '')),
        ]);
        (new Activity())->log((int) current_user_id(), 'application_sent', ['application_id' => $id]);
        flash('Candidature envoyée.', 'success');
        redirect('/client/candidatures');
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
        json_response(['success' => true]);
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