<?php
declare(strict_types=1);

final class OfferController extends Controller
{
    private Offer $offers;

    public function __construct()
    {
        $this->offers = new Offer();
    }

    public function index(): void
    {
        AuthMiddleware::requireRole(['entreprise']);
        $offers = $this->offers->allByEntreprise((int) current_user_id());
        $this->render('entreprise/offers/index', ['title' => 'Mes offres', 'offers' => $offers]);
    }

    public function create(): void
    {
        AuthMiddleware::requireRole(['entreprise']);
        $this->requireVerified();
        $this->render('entreprise/offers/create', ['title' => 'Nouvelle offre']);
    }

    /** Bloque la publication tant que l'entreprise n'est pas vérifiée. */
    private function requireVerified(): void
    {
        if (!is_verified_company()) {
            flash('Votre entreprise doit être vérifiée avant de publier des offres.', 'warning');
            redirect('/entreprise/verification');
        }
    }

    /** Rédaction assistée par IA d'une offre à partir de quelques éléments. */
    public function aiDraft(): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        $payload = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
        verify_csrf($payload['_csrf_token'] ?? null);

        $profile = (new Profile())->getByUserId((int) current_user_id()) ?? [];
        $result = (new OfferWriter())->generate(
            trim((string) ($payload['title'] ?? '')),
            trim((string) ($payload['sector'] ?? '')),
            trim((string) ($payload['skills'] ?? '')),
            trim((string) ($payload['contract_type'] ?? '')),
            (string) ($profile['bio'] ?? '')
        );

        $parts = array_filter([
            (string) ($result['description'] ?? ''),
            !empty($result['profile_required']) ? "\n\nProfil recherché :\n" . (string) $result['profile_required'] : '',
            !empty($result['benefits']) ? "\n\nCe que nous offrons :\n" . (string) $result['benefits'] : '',
        ]);

        json_response(['description' => trim(implode('', $parts))]);
    }

    public function store(): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        $this->requireVerified();
        verify_csrf();

        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));

        if ($title === '' || $description === '') {
            flash('Le titre et la description sont obligatoires.', 'danger');
            redirect('/entreprise/offres/new');
        }

        $this->offers->create($this->offerPayload((int) current_user_id()));
        flash('Offre créée avec succès.', 'success');
        redirect('/entreprise/offres');
    }

    public function edit(string $id): void
    {
        AuthMiddleware::requireRole(['entreprise']);
        $offer = $this->offers->findById((int) $id);
        if (!$offer || (int) $offer['entreprise_id'] !== (int) current_user_id()) {
            abort(404, 'Offre introuvable.');
        }

        $this->render('entreprise/offers/edit', ['title' => 'Modifier une offre', 'offer' => $offer]);
    }

    public function update(string $id): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        verify_csrf();

        $offer = $this->offers->findById((int) $id);
        if (!$offer || (int) $offer['entreprise_id'] !== (int) current_user_id()) {
            abort(404, 'Offre introuvable.');
        }

        $this->offers->update((int) $id, $this->offerPayload((int) current_user_id()));
        flash('Offre mise à jour.', 'success');
        redirect('/entreprise/offres');
    }

    public function destroy(string $id): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        verify_csrf();

        $offer = $this->offers->findById((int) $id);
        if (!$offer || (int) $offer['entreprise_id'] !== (int) current_user_id()) {
            abort(404, 'Offre introuvable.');
        }

        $this->offers->softDelete((int) $id);
        flash('Offre fermée.', 'success');
        redirect('/entreprise/offres');
    }

    /** Page publique d'une offre via URL propre /jobs/{slug}-{id}. */
    public function showPublicBySlug(string $slug): void
    {
        if (!preg_match('/-(\d+)$/', $slug, $m)) {
            abort(404, 'Offre introuvable.');
        }
        $offer = $this->offers->findById((int) $m[1]);
        if (!$offer) {
            abort(404, 'Offre introuvable.');
        }

        // 301 vers l'URL canonique si le slug fourni ne correspond pas (évite le
        // contenu dupliqué en SEO).
        $canonical = offer_url($offer);
        if (!str_ends_with($canonical, '/' . $slug)) {
            redirect($canonical, 301);
        }

        $company = (new User())->findById((int) ($offer['entreprise_id'] ?? 0));
        $location = trim((string) ($offer['location'] ?? ''));

        $this->render('pages/offer-public', [
            'title' => (string) ($offer['title'] ?? 'Offre') . ($location !== '' ? ' — ' . $location : ''),
            'offer' => $offer,
            'companyName' => (string) ($company['name'] ?? ''),
            'metaDescription' => mb_substr(trim((string) preg_replace('/\s+/', ' ', (string) ($offer['description'] ?? ''))), 0, 160),
        ]);
    }

    /** Ancienne URL /offres/{id} → redirection permanente vers l'URL propre. */
    public function redirectLegacyOffer(string $id): never
    {
        $offer = $this->offers->findById((int) $id);
        if (!$offer) {
            abort(404, 'Offre introuvable.');
        }
        redirect(offer_url($offer), 301);
    }

    private function offerPayload(int $entrepriseId): array
    {
        return [
            'entreprise_id' => $entrepriseId,
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'type' => (string) ($_POST['type'] ?? 'emploi'),
            'contract_type' => trim((string) ($_POST['contract_type'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'remote_ok' => !empty($_POST['remote_ok']),
            'salary_min' => $_POST['salary_min'] ?? null,
            'salary_max' => $_POST['salary_max'] ?? null,
            'skills_required' => array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', (string) ($_POST['skills_required'] ?? '')) ?: []))),
            'category_id' => $_POST['category_id'] ?? null,
            'status' => (string) ($_POST['status'] ?? 'active'),
            'expires_at' => $_POST['expires_at'] ?? null,
        ];
    }
}