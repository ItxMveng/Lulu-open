<?php
declare(strict_types=1);

final class PageController extends Controller
{
    public function home(): void
    {
        $pdo = db();
        $count = static fn (string $sql): int => (int) $pdo->query($sql)->fetchColumn();
        $this->render('pages/home', [
            'title' => 'Recrutement & talents',
            'fullWidth' => true,
            'categories' => (new Category())->all(),
            'homeStats' => [
                'talents' => $count("SELECT COUNT(*) FROM users WHERE role='client' AND status='active'"),
                'offers' => $count("SELECT COUNT(*) FROM offers WHERE status='active'"),
                'categories' => $count('SELECT COUNT(*) FROM categories'),
                'companies' => $count("SELECT COUNT(*) FROM users WHERE role='entreprise' AND verification_status='verified'"),
            ],
        ]);
    }

    public function about(): void
    {
        $this->render('pages/about', ['title' => 'À propos', 'fullWidth' => true]);
    }

    public function contact(): void
    {
        $this->render('pages/contact', ['title' => 'Contact']);
    }

    public function handleContactForm(): void
    {
        verify_csrf();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            store_old_input($_POST);
            flash('Merci de compléter correctement le formulaire de contact.', 'danger');
            redirect('/contact');
        }

        $html = sprintf(
            '<p><strong>%s</strong> (%s) vous a contacté.</p><p>%s</p>',
            e($name),
            e($email),
            nl2br(e($message))
        );

        MailHelper::send((string) env('MAIL_FROM_ADDRESS', 'contact@localhost'), 'Nouveau message de contact', $html, $message);
        clear_old_input();
        flash('Votre message a bien été envoyé. Nous revenons vers vous rapidement.', 'success');
        redirect('/contact');
    }

    public function cgu(): void
    {
        $this->render('pages/cgu', ['title' => 'Conditions générales']);
    }

    public function privacy(): void
    {
        $this->render('pages/privacy', ['title' => 'Confidentialité']);
    }

    public function legal(): void
    {
        $this->render('pages/legal', ['title' => 'Mentions légales']);
    }

    public function services(): void
    {
        $this->render('pages/services', [
            'title' => 'Prestations',
            'fullWidth' => true,
            'categories' => (new Category())->all(),
            'metaDescription' => 'Trouvez le prestataire ou le freelance idéal en Afrique : développeurs, designers, artisans, comptables et plus. Profils vérifiés, contact direct.',
            'metaKeywords' => 'freelance Afrique, prestataire, services, développeur, designer, artisan, mission freelance, talents',
        ]);
    }

    public function emplois(): void
    {
        $this->render('pages/emplois', [
            'title' => 'Offres et recrutement',
            'fullWidth' => true,
            'offers' => array_slice((new Offer())->publicSearch([]), 0, 6),
            'categories' => (new Category())->all(),
            'metaDescription' => 'Offres d\'emploi, missions et stages en Afrique. Postulez en un clic avec un CV et une lettre optimisés par l\'IA. Entreprises vérifiées.',
            'metaKeywords' => 'offres emploi Afrique, recrutement, jobs, stage, mission, candidature, CV IA, emploi Sénégal Côte d\'Ivoire Cameroun',
        ]);
    }

    public function pricing(): void
    {
        $this->render('pages/pricing', ['title' => 'Tarifs']);
    }
}