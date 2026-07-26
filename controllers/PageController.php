<?php
declare(strict_types=1);

final class PageController extends Controller
{
    public function home(): void
    {
        $this->render('pages/home', [
            'title' => 'Recrutement & talents',
            'fullWidth' => true,
            'categories' => (new Category())->all(),
        ]);
    }

    public function about(): void
    {
        $this->render('pages/about', ['title' => 'À propos']);
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
        $this->render('pages/services', ['title' => 'Prestations']);
    }

    public function emplois(): void
    {
        $this->render('pages/emplois', ['title' => 'Offres et recrutement']);
    }

    public function pricing(): void
    {
        $this->render('pages/pricing', ['title' => 'Tarifs']);
    }
}