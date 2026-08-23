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
        // Positionnement de marque + types d'opportunités (Sprint 2)
        'Les talents d\'Afrique, les opportunités du monde' => 'African talent, global opportunities',
        'Emploi, freelance, missions et recrutement : LULU-OPEN connecte les talents d\'Afrique aux bonnes opportunités, ici et à l\'international — simplement, en confiance, avec l\'IA.'
            => 'Jobs, freelancing, projects and hiring: LULU-OPEN connects African talent to the right opportunities, at home and abroad — simple, trusted, AI-powered.',
        'Emploi' => 'Jobs', 'Missions & freelance' => 'Projects & freelance', 'Services' => 'Services', 'International' => 'International',
        // Titres de page + chips vitrine
        'Recrutement & talents' => 'Recruiting & talent',
        'Développeur' => 'Developer', 'Designer' => 'Designer', 'Data Analyst' => 'Data Analyst',
        'Entreprise vérifiée' => 'Verified company', 'Cuisinier' => 'Chef',
        'Compétences, expériences, services proposés.' => 'Skills, experience, services offered.',
        'Candidatez en un clic avec votre CV et une lettre.' => 'Apply in one click with your resume and cover letter.',
        'Les entreprises vous trouvent et vous écrivent.' => 'Companies find you and reach out.',
        'Emploi, mission ou stage, en quelques minutes.' => 'Job, project or internship, in minutes.',
        'Centralisées, avec analyse IA à la clé.' => 'Centralized, with AI analysis built in.',
        'Recherchez et contactez directement les profils.' => 'Search and contact profiles directly.',
        'Trouver une opportunité' => 'Find an opportunity', 'Recruter un talent' => 'Hire a talent',
        // Tarifs
        'Tarifs simples et transparents' => 'Simple, transparent pricing',
        'Choisissez le plan qui vous ressemble' => 'Choose the plan that fits you',
        'Commencez gratuitement, évoluez quand vous en avez besoin.' => 'Start free, upgrade when you need to.',
        'Populaire' => 'Popular', '/mois' => '/month', 'Gratuit' => 'Free', 'Devise' => 'Currency',
        'Devise détectée selon votre position. Les tarifs sont convertis depuis l\'euro (à titre indicatif).'
            => 'Currency detected from your location. Prices are converted from euro (for reference).',
        'Choisir ce plan' => 'Choose this plan', 'Candidats & talents' => 'Candidates & talents',
        'Pour postuler et proposer vos services.' => 'To apply and offer your services.',
        'Pour publier des offres et recruter.' => 'To post offers and hire.',
        'Aucun plan disponible.' => 'No plan available.',
        // Noms de plans + fonctionnalités (catalogue, traduits à l'affichage)
        'Client Free' => 'Talent Free', 'Client Pro' => 'Talent Pro',
        'Entreprise Starter' => 'Company Starter', 'Entreprise Pro' => 'Company Pro', 'Entreprise Business' => 'Company Business',
        'Recherche basique' => 'Basic search', '3 favoris maximum' => 'Up to 3 favorites',
        'Messagerie illimitee' => 'Unlimited messaging', 'Favoris illimites' => 'Unlimited favorites', 'Alertes email' => 'Email alerts',
        '1 offre active' => '1 active offer', 'Profil basique' => 'Basic profile',
        'Offres illimitees' => 'Unlimited offers', 'Modules IA' => 'AI modules', 'Messagerie prioritaire' => 'Priority messaging',
        'Tout le plan Pro' => 'Everything in Pro', 'Badge verifie' => 'Verified badge', 'Statistiques avancees' => 'Advanced statistics',
        // Accueil conversion (Sprint 3)
        'Je cherche un emploi ou une mission' => 'I am looking for a job or a project',
        'Parcourez les offres et prestations disponibles.' => 'Browse available offers and services.',
        'Je cherche un talent' => 'I am looking for a talent',
        'Trouvez le bon profil ou publiez une offre.' => 'Find the right profile or post an offer.',
        'Dernières opportunités' => 'Latest opportunities',
        'Des offres réelles publiées par des entreprises vérifiées.' => 'Real offers posted by verified companies.',
        'Voir toutes les opportunités' => 'See all opportunities',
        'Les premières offres arrivent bientôt' => 'The first offers are coming soon',
        'Créez votre profil dès maintenant pour être alerté des nouvelles opportunités.' => 'Create your profile now to get notified of new opportunities.',
        'Créer mon profil' => 'Create my profile',
        'Pourquoi faire confiance à LULU-OPEN ?' => 'Why trust LULU-OPEN?',
        'Une plateforme pensée pour vous protéger et vous faire gagner du temps.' => 'A platform built to protect you and save you time.',
        'Chaque entreprise est contrôlée par notre équipe avant de publier une offre.' => 'Every company is checked by our team before posting an offer.',
        'Échangez en toute sécurité, sans partager vos coordonnées trop tôt.' => 'Chat safely, without sharing your contact details too early.',
        'Signalement des annonces' => 'Report listings',
        'Un doute sur une offre ? Signalez-la, notre équipe vérifie.' => 'Unsure about an offer? Report it and our team will check.',
        'L\'IA au service de la mise en relation' => 'AI that powers the match',
        'L\'IA vous aide à postuler et à recruter, sans jamais remplacer l\'humain.' => 'AI helps you apply and hire, without ever replacing the human touch.',
        'Pensée pour l\'Afrique' => 'Built for Africa',
        'Devise locale, marché africain, ouverture internationale.' => 'Local currency, African market, global reach.',
        'Vos données protégées' => 'Your data protected',
        'Profil visible selon votre choix, compte supprimable à tout moment.' => 'Profile visible on your terms, account deletable anytime.',
        'Une plateforme en croissance' => 'A growing platform',
        'Rejoignez les talents et les entreprises déjà présents.' => 'Join the talents and companies already on board.',
        // Carte d'offre
        'Mission' => 'Project', 'Stage' => 'Internship', 'Télétravail' => 'Remote',
        'Non précisé' => 'Not specified', 'Voir l\'offre' => 'View offer',
        // Accessibilité
        'Aller au contenu' => 'Skip to content',
        // Page offre publique
        'Accueil' => 'Home', 'Offres' => 'Offers', 'Vérifiée' => 'Verified', 'Publiée le' => 'Posted on',
        'Description du poste' => 'Job description',
        'Connectez-vous en tant que candidat pour postuler à cette offre.' => 'Sign in as a candidate to apply to this offer.',
        'Envoyez votre candidature avec votre CV et une lettre de motivation.' => 'Send your application with your resume and cover letter.',
        'Vous consultez cette offre en tant qu\'entreprise.' => 'You are viewing this offer as a company.',
        'Consultation administrateur.' => 'Administrator view.',
        // Meta descriptions (SEO) localisées
        'LULU-OPEN, la marketplace africaine de l\'emploi et des talents : trouvez un job, un freelance ou un candidat, postulez en un clic et boostez votre CV avec l\'IA.'
            => 'LULU-OPEN, the African marketplace for jobs and talent: find a job, a freelancer or a candidate, apply in one click and boost your resume with AI.',
        'Trouvez le prestataire ou le freelance idéal en Afrique : développeurs, designers, artisans, comptables et plus. Profils vérifiés, contact direct.'
            => 'Find the ideal freelancer or service provider in Africa: developers, designers, craftspeople, accountants and more. Verified profiles, direct contact.',
        'Offres d\'emploi, missions et stages en Afrique. Postulez en un clic avec un CV et une lettre optimisés par l\'IA. Entreprises vérifiées.'
            => 'Jobs, projects and internships in Africa. Apply in one click with an AI-optimized resume and cover letter. Verified companies.',
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
        'Comment ça marche' => 'How it works', 'Nos chiffres' => 'Our numbers', 'Défilez' => 'Scroll',
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
