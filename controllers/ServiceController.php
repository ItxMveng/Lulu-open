<?php
declare(strict_types=1);

/**
 * Gestion des prestations (services) proposées par un prestataire (rôle client).
 */
final class ServiceController extends Controller
{
    private Service $services;

    public function __construct()
    {
        $this->services = new Service();
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
            'categoriesList' => array_column((new Category())->all(), 'name'),
        ]);
    }

    public function store(): void
    {
        verify_csrf();
        $userId = (int) current_user_id();

        if (!$this->validate($_POST)) {
            store_old_input($_POST);
            $this->redirect('/client/services/nouveau');
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
            'categoriesList' => array_column((new Category())->all(), 'name'),
        ]);
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

        $this->services->update((int) $id, $userId, $_POST);
        flash('Prestation mise à jour.', 'success');
        $this->redirect('/client/services');
    }

    public function destroy(string $id): void
    {
        verify_csrf();
        $this->services->delete((int) $id, (int) current_user_id());
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
