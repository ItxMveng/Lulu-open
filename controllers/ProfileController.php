<?php
declare(strict_types=1);

final class ProfileController extends Controller
{
    private Profile $profiles;
    private User $users;

    public function __construct()
    {
        $this->profiles = new Profile();
        $this->users = new User();
    }

    public function showPublic(string $id): void
    {
        $profile = $this->profiles->getPublicProfile((int) $id);
        if (!$profile) {
            abort(404, 'Profil introuvable.');
        }

        $this->render('pages/profile-public', [
            'title' => 'Profil public',
            'profile' => $profile,
        ]);
    }

    public function showEditClient(): void
    {
        AuthMiddleware::requireRole(['client']);
        $profile = $this->profiles->getByUserId((int) current_user_id());
        $this->render('client/profile-edit', ['title' => 'Mon profil client', 'profile' => $profile]);
    }

    public function showEditEntreprise(): void
    {
        AuthMiddleware::requireRole(['entreprise']);
        $profile = $this->profiles->getByUserId((int) current_user_id());
        $this->render('entreprise/profile-edit', ['title' => 'Mon profil entreprise', 'profile' => $profile]);
    }

    public function handleUpdate(): never
    {
        AuthMiddleware::requireAuth();
        verify_csrf();

        $userId = (int) current_user_id();
        $role = (string) current_role();
        $name = trim((string) ($_POST['name'] ?? auth_user()['name'] ?? ''));

        if ($name === '') {
            flash('Le nom est obligatoire.', 'danger');
            redirect($role === 'entreprise' ? '/entreprise/profile/edit' : '/client/profile/edit');
        }

        $this->users->updateProfile($userId, ['name' => $name]);

        $profile = $this->profiles->getByUserId($userId) ?? [];
        $profileData = [
            'display_name' => $name,
            'bio' => trim((string) ($_POST['bio'] ?? '')),
            'location' => trim((string) ($_POST['location'] ?? '')),
            'photo_path' => $profile['photo_path'] ?? null,
            'type' => $role === 'entreprise' ? (string) ($_POST['type'] ?? ($profile['type'] ?? 'mixte')) : 'services',
            'categories' => $this->parseList($_POST['categories'] ?? ''),
            'skills' => $this->parseList($_POST['skills'] ?? ''),
            'languages' => $this->parseList($_POST['languages'] ?? ''),
            'hourly_rate' => $_POST['hourly_rate'] ?? null,
            'availability' => trim((string) ($_POST['availability'] ?? '')),
            'portfolio' => $this->parseList($_POST['portfolio'] ?? ''),
            'certifications' => $this->parseList($_POST['certifications'] ?? ''),
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
        ];

        $this->profiles->save($userId, $profileData);
        $_SESSION['user']['name'] = $name;

        flash('Profil mis à jour avec succès.', 'success');
        redirect($role === 'entreprise' ? '/entreprise/profile/edit' : '/client/profile/edit');
    }

    public function uploadPhoto(): never
    {
        AuthMiddleware::requireAuth();
        verify_csrf();

        $profile = $this->profiles->getByUserId((int) current_user_id()) ?? [];
        $path = UploadHelper::storeUploadedFile(
            $_FILES['photo'] ?? [],
            'photos',
            ['image/jpeg', 'image/png'],
            2 * 1024 * 1024,
            $profile['photo_path'] ?? null
        );

        $this->profiles->save((int) current_user_id(), [
            'display_name' => $profile['display_name'] ?? ($_SESSION['user']['name'] ?? 'Profil'),
            'type' => $profile['type'] ?? (current_role() === 'entreprise' ? 'mixte' : 'services'),
            'bio' => $profile['bio'] ?? null,
            'location' => $profile['location'] ?? null,
            'photo_path' => $path,
            'categories' => $this->decodeJsonList($profile['categories'] ?? null),
            'skills' => $this->decodeJsonList($profile['skills'] ?? null),
            'languages' => $this->decodeJsonList($profile['languages'] ?? null),
            'portfolio' => $this->decodeJsonList($profile['portfolio'] ?? null),
            'certifications' => $this->decodeJsonList($profile['certifications'] ?? null),
            'hourly_rate' => $profile['hourly_rate'] ?? null,
            'availability' => $profile['availability'] ?? null,
            'is_visible' => (int) ($profile['is_visible'] ?? 1),
        ]);

        flash('Photo mise à jour.', 'success');
        redirect(current_role() === 'entreprise' ? '/entreprise/profile/edit' : '/client/profile/edit');
    }

    public function uploadCV(): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        verify_csrf();

        $path = UploadHelper::storeUploadedFile(
            $_FILES['cv'] ?? [],
            'cv',
            ['application/pdf'],
            5 * 1024 * 1024
        );

        $updatePrimary = db()->prepare('UPDATE cv_documents SET is_primary = 0 WHERE user_id = :user_id');
        $updatePrimary->execute(['user_id' => current_user_id()]);

        $statement = db()->prepare(
            'INSERT INTO cv_documents (user_id, file_path, file_name, uploaded_at, is_primary)
             VALUES (:user_id, :file_path, :file_name, NOW(), 1)'
        );
        $statement->execute([
            'user_id' => current_user_id(),
            'file_path' => $path,
            'file_name' => $_FILES['cv']['name'] ?? 'cv.pdf',
        ]);

        flash('CV importé avec succès.', 'success');
        redirect('/entreprise/profile/edit');
    }

    private function parseList(string|array $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(static fn ($item): string => trim((string) $item), $value)));
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $value) ?: [])));
    }

    private function decodeJsonList(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}