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
            'latestOffers' => array_slice((new Offer())->publicSearch([]), 0, 6),
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

    /** Présentation publique des fonctionnalités IA réellement disponibles. */
    public function ai(): void
    {
        $this->render('pages/ai', [
            'title' => 'IA & matching',
            'fullWidth' => true,
            'metaDescription' => 'L\'IA de LULU-OPEN accélère la mise en relation : analyse et génération de CV et de lettres pour les candidats, rédaction d\'offres et classement des candidatures pour les recruteurs.',
            'metaKeywords' => 'IA recrutement, analyse CV, génération CV IA, lettre de motivation IA, matching candidats, tri candidatures',
        ]);
    }

    /** Hub listant tous les domaines (maillage interne + SEO). */
    public function categoriesHub(): void
    {
        $this->render('pages/categories-hub', [
            'title' => 'Domaines & catégories',
            'categories' => (new Category())->all(),
            'metaDescription' => 'Explorez tous les domaines de LULU-OPEN : développement, design, marketing, finance, artisanat et plus. Talents et opportunités en Afrique.',
        ]);
    }

    /** Page d'atterrissage d'une catégorie : vrais talents (+ offres si catégorisées). */
    public function category(string $slug): void
    {
        $category = (new Category())->findBySlug($slug);
        if (!$category) {
            abort(404, 'Catégorie introuvable.');
        }
        $name = (string) $category['name'];
        $talents = (new Profile())->search(['category' => $name, 'per_page' => 6]);
        $offers = (new Offer())->publicByCategory((int) $category['id'], 6);
        // Anti thin-content : si la page n'a aucune donnée réelle, on la désindexe.
        $isEmpty = empty($talents['items']) && empty($offers);

        $this->render('pages/category-landing', [
            'title' => $name . ' — ' . t('talents & opportunités'),
            'fullWidth' => true,
            'category' => $category,
            'talents' => $talents,
            'offers' => $offers,
            'allCategories' => (new Category())->all(),
            'noindex' => $isEmpty,
            'metaDescription' => t('Trouvez des talents et des opportunités en') . ' ' . $name . '. ' . t('Profils vérifiés, contact direct, sur LULU-OPEN.'),
            'metaKeywords' => $name . ', talents, freelance, recrutement, offres, Afrique',
        ]);
    }

    public function pricing(): void
    {
        $this->render('pages/pricing', ['title' => 'Tarifs']);
    }
}