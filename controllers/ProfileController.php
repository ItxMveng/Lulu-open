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
        $userId = (int) current_user_id();
        $profile = $this->profiles->getByUserId($userId);
        $this->render('client/profile-edit', [
            'title' => 'Mon profil',
            'profile' => $profile,
            'cvDocuments' => (new CvDocument())->allForUser($userId),
            'categoriesList' => array_column((new Category())->all(), 'name'),
            'skillsList' => Reference::commonSkills(),
            'languagesList' => Reference::languages(),
            'maxCategories' => SubscriptionHelper::maxCategories($userId),
        ]);
    }

    public function showEditEntreprise(): void
    {
        AuthMiddleware::requireRole(['entreprise']);
        $profile = $this->profiles->getByUserId((int) current_user_id());
        $this->render('entreprise/profile-edit', [
            'title' => 'Mon profil entreprise',
            'profile' => $profile,
            'categoriesList' => array_column((new Category())->all(), 'name'),
            'languagesList' => Reference::languages(),
        ]);
    }

    /** Optimise le contenu du profil (présentation/bio) via IA. */
    public function enhance(): never
    {
        AuthMiddleware::requireAuth();
        $payload = json_decode((string) file_get_contents('php://input'), true) ?: $_POST;
        verify_csrf($payload['_csrf_token'] ?? null);

        $role = (string) current_role();
        $isCompany = $role === 'entreprise';
        $bio = trim((string) ($payload['bio'] ?? ''));
        $skills = trim((string) ($payload['skills'] ?? ''));
        $name = (string) (auth_user()['name'] ?? '');

        $ai = new IAProvider();
        if (!$ai->enabled()) {
            json_response(['error' => "L'IA n'est pas configurée."], 422);
        }

        $who = $isCompany ? "la présentation d'une entreprise auprès de candidats" : "la présentation professionnelle d'un candidat auprès de recruteurs";
        $system = "Tu es un expert en personal branding. Réécris et améliore {$who} EN FRANÇAIS : "
            . "texte fluide, professionnel, engageant, orienté valeur ajoutée, 3 à 5 phrases, sans exagération ni fausse information. "
            . "Réponds UNIQUEMENT avec le texte amélioré, sans guillemets ni commentaire.";
        $user = "Nom : {$name}\nCompétences / secteurs : {$skills}\nPrésentation actuelle : " . ($bio !== '' ? $bio : '(vide — rédige une présentation à partir des compétences)');

        $result = $ai->complete($system, $user, ['temperature' => 0.6]);
        if ($result === null || trim($result) === '') {
            json_response(['error' => "L'IA n'a pas pu générer de proposition."], 502);
        }

        json_response(['bio' => trim($result)]);
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
            'skills' => array_values(array_unique(array_merge(
                $this->parseList($_POST['skills'] ?? ''),
                $this->parseList($_POST['skills_extra'] ?? '')
            ))),
            'languages' => $this->parseList($_POST['languages'] ?? ''),
            'hourly_rate' => $_POST['hourly_rate'] ?? null,
            'availability' => trim((string) ($_POST['availability'] ?? '')),
            'portfolio' => $this->parseList($_POST['portfolio'] ?? ''),
            'certifications' => $this->parseList($_POST['certifications'] ?? ''),
            'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
        ];

        // Talent : au moins un domaine obligatoire, nombre limité selon l'abonnement.
        if ($role === 'client') {
            if (empty($profileData['categories'])) {
                store_old_input($_POST);
                flash('Choisissez au moins un domaine pour être visible dans les recherches.', 'danger');
                redirect('/client/profile/edit');
            }
            $max = SubscriptionHelper::maxCategories($userId);
            if (count($profileData['categories']) > $max) {
                $profileData['categories'] = array_slice($profileData['categories'], 0, $max);
                flash("Votre plan permet {$max} domaine(s). Passez à un plan supérieur pour en sélectionner davantage.", 'warning');
            }

            // Un profil n'apparaît dans la recherche que s'il est minimalement
            // complet. Sinon on force la non-visibilité (même si la case est cochée)
            // et on indique ce qu'il reste à renseigner.
            $missing = [];
            if (mb_strlen((string) ($profileData['bio'] ?? '')) < 40) { $missing[] = 'une présentation (40 caractères minimum)'; }
            if (trim((string) ($profileData['location'] ?? '')) === '') { $missing[] = 'la localisation'; }
            if (empty($profileData['skills'])) { $missing[] = 'au moins une compétence'; }

            if ($missing !== []) {
                $wantedVisible = $profileData['is_visible'] === 1;
                $profileData['is_visible'] = 0;
                if ($wantedVisible) {
                    flash('Profil enregistré. Pour apparaître dans la recherche, complétez encore : ' . implode(', ', $missing) . '.', 'warning');
                }
            }
        }

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
        try {
            $path = UploadHelper::storeUploadedFile(
                $_FILES['photo'] ?? [],
                'photos',
                ['image/jpeg', 'image/png', 'image/webp'],
                3 * 1024 * 1024,
                $profile['photo_path'] ?? null
            );
        } catch (Throwable $e) {
            flash('Photo non mise à jour : ' . $this->uploadErrorMessage($e), 'danger');
            redirect(current_role() === 'entreprise' ? '/entreprise/profile/edit' : '/client/profile/edit');
        }

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
        AuthMiddleware::requireRole(['client']);
        verify_csrf();

        try {
            $path = UploadHelper::storeUploadedFile(
                $_FILES['cv'] ?? [],
                'cv',
                [
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'image/jpeg',
                    'image/png',
                ],
                8 * 1024 * 1024
            );
        } catch (Throwable $e) {
            flash('CV non importé : ' . $this->uploadErrorMessage($e), 'danger');
            redirect('/client/profile/edit');
        }

        $cvDocuments = new CvDocument();
        $isFirst = empty($cvDocuments->allForUser((int) current_user_id()));
        $cvDocuments->add((int) current_user_id(), $path, (string) ($_FILES['cv']['name'] ?? 'cv'), $isFirst);

        flash('CV importé avec succès.', 'success');
        redirect('/client/profile/edit');
    }

    private function uploadErrorMessage(Throwable $e): string
    {
        $msg = $e->getMessage();
        if (str_contains($msg, 'MIME')) {
            return 'format non accepté. Utilisez un PDF, un document Word (.doc/.docx) ou une image (JPG/PNG).';
        }
        if (str_contains($msg, 'taille')) {
            return 'fichier trop volumineux (8 Mo maximum).';
        }
        return 'vérifiez le fichier et réessayez.';
    }

    public function setPrimaryCV(string $id): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf();
        (new CvDocument())->setPrimary((int) $id, (int) current_user_id());
        flash('CV principal mis à jour.', 'success');
        redirect('/client/profile/edit');
    }

    public function deleteCV(string $id): never
    {
        AuthMiddleware::requireRole(['client']);
        verify_csrf();
        (new CvDocument())->delete((int) $id, (int) current_user_id());
        flash('CV supprimé.', 'success');
        redirect('/client/profile/edit');
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