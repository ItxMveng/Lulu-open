<?php
declare(strict_types=1);

/** Gestion des vérifications d'entreprises côté super-admin. */
final class AdminVerificationController extends Controller
{
    private CompanyVerification $verifications;

    public function __construct()
    {
        $this->verifications = new CompanyVerification();
    }

    public function index(): void
    {
        AuthMiddleware::requireRole(['admin']);
        $this->render('admin/verifications/index', [
            'title' => 'Vérifications entreprises',
            'dossiers' => $this->verifications->pending(),
        ], 'admin');
    }

    public function review(string $id): never
    {
        AuthMiddleware::requireRole(['admin']);
        verify_csrf();

        $dossier = $this->verifications->findById((int) $id);
        if (!$dossier) {
            flash('Dossier introuvable.', 'danger');
            redirect('/admin/verifications');
        }

        $decision = (string) ($_POST['decision'] ?? '');
        $note = trim((string) ($_POST['admin_note'] ?? ''));
        if (!in_array($decision, ['verified', 'rejected'], true)) {
            flash('Décision invalide.', 'danger');
            redirect('/admin/verifications');
        }

        $this->verifications->review((int) $dossier['user_id'], $decision, $note);

        if (!empty($dossier['email'])) {
            if ($decision === 'verified') {
                AppMailer::verificationApproved((string) $dossier['email'], (string) $dossier['name']);
            } else {
                AppMailer::verificationRejected((string) $dossier['email'], (string) $dossier['name'], $note);
            }
        }

        flash($decision === 'verified' ? 'Entreprise vérifiée, email envoyé.' : 'Dossier refusé, email envoyé.', 'success');
        redirect('/admin/verifications');
    }
}
