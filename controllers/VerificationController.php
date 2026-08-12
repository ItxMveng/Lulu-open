<?php
declare(strict_types=1);

/** Dossier de vérification d'une entreprise (soumission côté entreprise). */
final class VerificationController extends Controller
{
    private CompanyVerification $verifications;

    public function __construct()
    {
        $this->verifications = new CompanyVerification();
    }

    public function show(): void
    {
        AuthMiddleware::requireRole(['entreprise']);
        $this->render('entreprise/verification', [
            'title' => 'Vérification de l\'entreprise',
            'dossier' => $this->verifications->findByUserId((int) current_user_id()),
            'status' => current_verification_status(),
        ]);
    }

    public function submit(): never
    {
        AuthMiddleware::requireRole(['entreprise']);
        verify_csrf();
        $userId = (int) current_user_id();

        $legal = trim((string) ($_POST['legal_name'] ?? ''));
        if ($legal === '') {
            flash('La raison sociale est obligatoire.', 'danger');
            store_old_input($_POST);
            redirect('/entreprise/verification');
        }

        $documentPath = null;
        if (!empty($_FILES['document']['name'])) {
            try {
                $documentPath = UploadHelper::storeUploadedFile(
                    $_FILES['document'], 'verifications',
                    ['application/pdf', 'image/jpeg', 'image/png'], 8 * 1024 * 1024
                );
            } catch (Throwable $e) {
                flash('Document refusé : PDF ou image (8 Mo max).', 'danger');
                store_old_input($_POST);
                redirect('/entreprise/verification');
            }
        }

        $this->verifications->submit($userId, [
            'legal_name' => $legal,
            'registration_number' => trim((string) ($_POST['registration_number'] ?? '')),
            'country' => trim((string) ($_POST['country'] ?? '')),
            'website' => trim((string) ($_POST['website'] ?? '')),
            'sector' => trim((string) ($_POST['sector'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
            'document_path' => $documentPath,
        ]);
        $_SESSION['user']['verification_status'] = 'pending';

        $user = auth_user() ?? [];
        if (!empty($user['email'])) {
            AppMailer::verificationSubmitted((string) $user['email'], (string) ($user['name'] ?? ''));
        }

        clear_old_input();
        flash('Dossier envoyé. Notre équipe le vérifiera sous 24–48h.', 'success');
        redirect('/entreprise/dashboard');
    }
}
