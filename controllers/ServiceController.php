<?php
declare(strict_types=1);

/**
 * Gestion des prestations (services) proposées par un prestataire (rôle client).
 */
final class ServiceController extends Controller
{
    private Service $services;
    private Profile $profiles;

    public function __construct()
    {
        $this->services = new Service();
        $this->profiles = new Profile();
    }

    public function index(): void
    {
        $userId = (int) current_user_id();
        $this->render('client/services/index', [
            'title' => 'Mes prestations',
            'services' => $this->services->forUser($userId),
        ]);
    }

    public function create(): void
    {
        $this->render('client/services/form', [
            'title' => 'Nouvelle prestation',
            'service' => null,
            'categoriesList' => $this->userDomains(),
        ]);
    }

    /** Domaines déclarés sur le profil du prestataire (pour restreindre le choix). */
    private function userDomains(): array
    {
        $profile = $this->profiles->getByUserId((int) current_user_id());
        $cats = $profile['categories'] ?? null;
        $cats = is_array($cats) ? $cats : (json_decode((string) $cats, true) ?: []);
        return array_values(array_filter(array_map('strval', $cats)));
    }

    public function store(): void
    {
        verify_csrf();
        $userId = (int) current_user_id();

        if (!$this->validate($_POST)) {
            store_old_input($_POST);
            $this->redirect('/client/services/nouveau');
        }

        if (!empty($_FILES['image']['name'])) {
            $rel = ImageOptimizer::processUpload($_FILES['image']);
            if ($rel === null) {
                flash("L'image n'a pas pu être traitée (formats acceptés : JPG, PNG, WebP, GIF).", 'warning');
            } else {
                $_POST['image_path'] = $rel;
            }
        }

        $this->services->create($userId, $_POST);
        flash('Prestation ajoutée avec succès.', 'success');
        $this->redirect('/client/services');
    }

    public function edit(string $id): void
    {
        $service = $this->services->find((int) $id);
        if ($service === null || (int) $service['user_id'] !== (int) current_user_id()) {
            flash('Prestation introuvable.', 'danger');
            $this->redirect('/client/services');
        }

        $this->render('client/services/form', [
            'title' => 'Modifier la prestation',
            'service' => $service,
            'categoriesList' => $this->userDomains(),
        ]);
    }

    /** Assistant IA : rédige/améliore la description d'une prestation. */
    public function aiDescribe(): never
    {
        $payload = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
        verify_csrf($payload['_csrf_token'] ?? null);

        $title = trim((string) ($payload['title'] ?? ''));
        $category = trim((string) ($payload['category'] ?? ''));
        $current = trim((string) ($payload['description'] ?? ''));
        if ($title === '') {
            json_response(['error' => 'Indiquez d\'abord un titre de prestation.'], 422);
        }

        $ai = new IAProvider();
        if (!$ai->enabled()) {
            json_response(['error' => "L'IA n'est pas configurée."], 422);
        }

        $system = "Tu es un expert en rédaction d'offres de services freelance EN FRANÇAIS. "
            . "Rédige une description de prestation claire, professionnelle et vendeuse : "
            . "1 phrase d'accroche, puis ce qui est inclus (livrables), la méthode, et le bénéfice client. "
            . "120 à 180 mots, ton engageant mais honnête, sans exagération. "
            . "Réponds UNIQUEMENT avec la description, sans titre, sans guillemets, sans markdown.";
        $user = "Titre de la prestation : {$title}\nDomaine : " . ($category !== '' ? $category : 'non précisé')
            . "\nDescription actuelle : " . ($current !== '' ? $current : '(vide — rédige à partir du titre)');

        $result = $ai->complete($system, $user, ['temperature' => 0.6]);
        if ($result === null || trim($result) === '') {
            json_response(['error' => "L'IA n'a pas pu générer de description."], 502);
        }

        json_response(['description' => trim($result)]);
    }

    public function update(string $id): void
    {
        verify_csrf();
        $userId = (int) current_user_id();
        $service = $this->services->find((int) $id);
        if ($service === null || (int) $service['user_id'] !== $userId) {
            flash('Prestation introuvable.', 'danger');
            $this->redirect('/client/services');
        }

        if (!$this->validate($_POST)) {
            store_old_input($_POST);
            $this->redirect('/client/services/' . (int) $id . '/edit');
        }

        // Image : nouvelle > suppression demandée > conservation de l'existante.
        $currentImage = (string) ($service['image_path'] ?? '');
        if (!empty($_FILES['image']['name'])) {
            $rel = ImageOptimizer::processUpload($_FILES['image']);
            if ($rel !== null) {
                UploadHelper::deleteRelativeFile($currentImage ?: null);
                $_POST['image_path'] = $rel;
            } else {
                flash("L'image n'a pas pu être traitée.", 'warning');
                $_POST['image_path'] = $currentImage;
            }
        } elseif (!empty($_POST['remove_image'])) {
            UploadHelper::deleteRelativeFile($currentImage ?: null);
            $_POST['image_path'] = null;
        } else {
            $_POST['image_path'] = $currentImage;
        }

        $this->services->update((int) $id, $userId, $_POST);
        flash('Prestation mise à jour.', 'success');
        $this->redirect('/client/services');
    }

    public function destroy(string $id): void
    {
        verify_csrf();
        $userId = (int) current_user_id();
        $service = $this->services->find((int) $id);
        if ($service !== null && (int) $service['user_id'] === $userId) {
            UploadHelper::deleteRelativeFile((string) ($service['image_path'] ?? '') ?: null);
        }
        $this->services->delete((int) $id, $userId);
        flash('Prestation supprimée.', 'success');
        $this->redirect('/client/services');
    }

    /** Validation minimale. */
    private function validate(array $input): bool
    {
        $title = trim((string) ($input['title'] ?? ''));
        if (mb_strlen($title) < 3) {
            flash('Le titre de la prestation est requis (3 caractères minimum).', 'danger');
            return false;
        }
        if (($input['price'] ?? '') !== '' && !is_numeric($input['price'])) {
            flash('Le prix doit être un nombre.', 'danger');
            return false;
        }
        return true;
    }
}
