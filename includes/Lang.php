<?php
declare(strict_types=1);

/**
 * Internationalisation légère (FR par défaut, EN).
 * Traduction par chaîne source : t('Connexion') renvoie 'Login' en anglais,
 * sinon la chaîne française d'origine (dégradation gracieuse si non traduite).
 */
final class Lang
{
    private const SUPPORTED = ['fr', 'en'];

    public static function current(): string
    {
        if (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], self::SUPPORTED, true)) {
            return (string) $_SESSION['lang'];
        }
        if (!isset($_SESSION['lang_detected'])) {
            $_SESSION['lang_detected'] = self::detect();
        }
        return (string) $_SESSION['lang_detected'];
    }

    public static function set(string $code): void
    {
        $code = strtolower(substr($code, 0, 2));
        if (in_array($code, self::SUPPORTED, true)) {
            $_SESSION['lang'] = $code;
        }
    }

    public static function isEnglish(): bool
    {
        return self::current() === 'en';
    }

    public static function t(string $fr): string
    {
        if (self::current() !== 'en') {
            return $fr;
        }
        return self::DICT[$fr] ?? $fr;
    }

    private static function detect(): string
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
        return str_starts_with($accept, 'en') ? 'en' : 'fr';
    }

    /** Dictionnaire FR => EN (surfaces visibles). */
    private const DICT = [
        // Navbar / commun
        'Prestations' => 'Services', 'Recrutement' => 'Recruiting', 'Tarifs' => 'Pricing', 'Contact' => 'Contact',
        'Connexion' => 'Login', 'Créer un compte' => 'Sign up', 'Déconnexion' => 'Log out',
        'Espace client' => 'My space', 'Espace entreprise' => 'Company space', 'Rechercher' => 'Search',
        'Mes candidatures' => 'My applications', 'Messages' => 'Messages', 'Abonnement' => 'Subscription',
        'Mes offres' => 'My offers', 'Candidatures' => 'Applications', 'Outils IA' => 'AI tools',
        'Vérification' => 'Verification', 'Tableau de bord' => 'Dashboard', 'Mon profil' => 'My profile',
        'Mon abonnement' => 'My subscription', 'Admin' => 'Admin', 'Mon compte' => 'My account',
        // Footer
        'Explorer' => 'Explore', 'Compte' => 'Account', 'Légal' => 'Legal',
        'Conditions générales' => 'Terms of use', 'Confidentialité' => 'Privacy', 'Mentions légales' => 'Legal notice',
        'Tous droits réservés.' => 'All rights reserved.',
        'La marketplace qui connecte les talents et les entreprises : offres, candidatures, messagerie et outils IA.'
            => 'The marketplace connecting talents and companies: offers, applications, messaging and AI tools.',
        // Home
        'La marketplace des talents et des entreprises' => 'The talents & companies marketplace',
        'Trouvez le bon' => 'Find the right', 'talent' => 'talent', 'décrochez la bonne' => 'land the right', 'mission' => 'job',
        'Candidats et prestataires d\'un côté, entreprises et recruteurs de l\'autre. Offres, candidatures, messagerie et outils IA — au même endroit.'
            => 'Candidates and freelancers on one side, companies and recruiters on the other. Offers, applications, messaging and AI tools — all in one place.',
        'Métier, compétence, poste…' => 'Job, skill, role…', 'Ville ou télétravail' => 'City or remote',
        'Populaire :' => 'Popular:', 'Explorez par catégorie' => 'Browse by category',
        'Des profils et des offres dans tous les domaines.' => 'Profiles and offers in every field.', 'Tout voir' => 'See all',
        'Deux parcours, une plateforme' => 'Two journeys, one platform',
        'Que vous cherchiez une opportunité ou un talent, tout est fluide.' => 'Whether you seek an opportunity or a talent, it is seamless.',
        'Candidats & prestataires' => 'Candidates & freelancers', 'Mettez-vous en avant' => 'Stand out',
        'Créez votre profil' => 'Create your profile', 'Postulez aux offres' => 'Apply to offers', 'Soyez contacté' => 'Get contacted',
        'Je suis un talent' => 'I am a talent', 'Entreprises & recruteurs' => 'Companies & recruiters',
        'Recrutez plus vite' => 'Hire faster', 'Publiez vos offres' => 'Post your offers',
        'Recevez les candidatures' => 'Receive applications', 'Sourcez les talents' => 'Source talents', 'Je recrute' => 'I am hiring',
        'Prêt à passer à la vitesse supérieure ?' => 'Ready to take it to the next level?',
        'Rejoignez LULU-OPEN gratuitement et connectez-vous aux bonnes opportunités.' => 'Join LULU-OPEN for free and connect to the right opportunities.',
        'Voir les tarifs' => 'See pricing',
        // Cookies
        'Respect de votre vie privée' => 'Your privacy matters', 'Refuser' => 'Decline', 'Accepter' => 'Accept',
        'En savoir plus' => 'Learn more',
        // Auth
        'Content de vous revoir' => 'Welcome back', 'Connectez-vous à votre espace.' => 'Sign in to your account.',
        'Email' => 'Email', 'Mot de passe' => 'Password', 'Mot de passe oublié ?' => 'Forgot password?',
        'Se connecter' => 'Sign in', 'Pas encore de compte ?' => 'No account yet?',
        'Créez votre compte' => 'Create your account', 'Gratuit — commencez en une minute.' => 'Free — get started in a minute.',
        'Déjà un compte ?' => 'Already have an account?', 'Nom complet' => 'Full name', 'Confirmation' => 'Confirmation',
        'Créer mon compte' => 'Create my account', 'Je m\'inscris en tant que' => 'I sign up as',
        'Candidat / Talent' => 'Candidate / Talent', 'Je postule et propose mes services' => 'I apply and offer my services',
        'Entreprise' => 'Company', 'Je publie des offres et recrute' => 'I post offers and hire',
        // Divers CTA
        'Rechercher un talent ou une offre' => 'Search a talent or an offer', 'Trouver une offre' => 'Find an offer',
        'Publier une offre' => 'Post an offer', 'Postuler maintenant' => 'Apply now', 'Contacter' => 'Contact',
        // Navbar connecté
        'Utilisateurs' => 'Users', 'Abonnements' => 'Subscriptions', 'Catégories' => 'Categories',
        // Catégories
        'Développement Web' => 'Web Development', 'Design & Créa' => 'Design & Creative', 'Rédaction' => 'Writing',
        'Data & IA' => 'Data & AI', 'Support & Admin' => 'Support & Admin', 'Commerce & Vente' => 'Sales',
        'Comptabilité & Finance' => 'Accounting & Finance', 'Ressources Humaines' => 'Human Resources',
        'Santé & Social' => 'Health & Social', 'Enseignement & Formation' => 'Teaching & Training',
        'Bâtiment & Travaux' => 'Construction', 'Artisanat & Métiers manuels' => 'Crafts & Trades',
        'Restauration & Hôtellerie' => 'Food & Hospitality', 'Beauté & Bien-être' => 'Beauty & Wellness',
        'Transport & Logistique' => 'Transport & Logistics', 'Juridique' => 'Legal',
        'Agriculture & Environnement' => 'Agriculture & Environment',
        // Formulaires recherche
        'Mots-clés' => 'Keywords', 'Pays' => 'Country', 'Tous les pays' => 'All countries', 'Ville' => 'City',
        'Trier par' => 'Sort by', 'Pertinence' => 'Relevance', 'Plus récents' => 'Most recent', 'Plus vus' => 'Most viewed',
        'Toutes les catégories' => 'All categories', 'Talents & prestations' => 'Talents & services', 'Offres d\'emploi' => 'Job offers',
        'Métier, compétence…' => 'Job, skill…', 'Ville…' => 'City…',
        // Sections landing enrichies
        'Pourquoi LULU-OPEN ?' => 'Why LULU-OPEN?', 'Questions fréquentes' => 'Frequently asked questions',
        'Ils nous font confiance' => 'They trust us', 'Commencer gratuitement' => 'Get started for free',
        'Comment ça marche' => 'How it works', 'Nos chiffres' => 'Our numbers',
        // Section fonctionnalités
        'Tout ce qu\'il vous faut, au même endroit' => 'Everything you need, in one place',
        'Une plateforme complète pensée pour l\'Afrique et ouverte sur le monde.' => 'A complete platform built for Africa and open to the world.',
        'Candidature en 1 clic' => 'One-click application',
        'Postulez avec votre CV et une lettre générée par l\'IA, sans friction.' => 'Apply with your resume and an AI-generated cover letter, frictionless.',
        'Assistant IA' => 'AI assistant',
        'Analyse de CV, génération de CV et de lettres adaptées à chaque offre.' => 'Resume analysis, resume and cover letter generation tailored to each offer.',
        'Messagerie sécurisée' => 'Secure messaging',
        'Échangez directement avec les recruteurs ou les candidats en toute sécurité.' => 'Chat directly with recruiters or candidates, securely.',
        'Entreprises vérifiées' => 'Verified companies',
        'Chaque entreprise est vérifiée : fini les arnaques, place à la confiance.' => 'Every company is verified: no more scams, only trust.',
        'Multi-devises' => 'Multi-currency',
        'Les tarifs s\'affichent automatiquement dans la devise de votre pays.' => 'Prices are automatically shown in your country\'s currency.',
        'Bilingue FR / EN' => 'Bilingual FR / EN',
        'Naviguez en français ou en anglais, selon votre préférence.' => 'Browse in French or English, as you prefer.',
        // Stats
        'talents actifs' => 'active talents', 'offres en ligne' => 'live offers',
        'domaines métiers' => 'job fields', 'entreprises vérifiées' => 'verified companies',
        // Témoignages
        'Ils en parlent mieux que nous' => 'They say it better than us',
        'Des talents et des entreprises qui avancent avec LULU-OPEN.' => 'Talents and companies moving forward with LULU-OPEN.',
        // FAQ
        'Questions fréquentes' => 'Frequently asked questions',
        'Tout ce que vous devez savoir avant de commencer.' => 'Everything you need to know before you start.',
        'Est-ce gratuit ?' => 'Is it free?',
        'Oui, la création de compte et la candidature sont gratuites. Des plans payants débloquent des fonctionnalités avancées.' => 'Yes, creating an account and applying are free. Paid plans unlock advanced features.',
        'Comment fonctionne l\'IA ?' => 'How does the AI work?',
        'Notre assistant analyse votre profil et l\'offre pour générer un CV et une lettre adaptés, et évaluer votre adéquation.' => 'Our assistant analyzes your profile and the offer to generate a tailored resume and cover letter, and assess your fit.',
        'Comment être sûr qu\'une entreprise est fiable ?' => 'How do I know a company is trustworthy?',
        'Toutes les entreprises passent par une vérification manuelle de notre équipe avant de pouvoir publier des offres.' => 'All companies go through a manual verification by our team before they can post offers.',
        'Dans quels pays êtes-vous présents ?' => 'In which countries are you present?',
        'La plateforme est pensée pour l\'Afrique et ouverte au monde entier, avec gestion automatique des devises locales.' => 'The platform is designed for Africa and open worldwide, with automatic local currency handling.',
    ];
}
