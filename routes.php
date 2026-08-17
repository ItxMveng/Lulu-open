<?php
declare(strict_types=1);

Router::get('/robots.txt', static function (): never {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /client\nDisallow: /entreprise\nDisallow: /messages\n\nSitemap: " . APP_URL . "/sitemap.xml\n";
    exit;
});
Router::get('/sitemap.xml', static function (): never {
    header('Content-Type: application/xml; charset=UTF-8');
    $urls = ['/', '/services', '/emplois', '/pricing', '/contact', '/about', '/cgu', '/privacy', '/legal', '/search'];
    $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($urls as $u) {
        $out .= '<url><loc>' . e(APP_URL . $u) . '</loc><changefreq>weekly</changefreq></url>';
    }
    foreach (db()->query("SELECT id FROM offers WHERE status='active' ORDER BY created_at DESC LIMIT 500")->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $out .= '<url><loc>' . e(APP_URL . '/offres/' . (int) $id) . '</loc></url>';
    }
    foreach (db()->query("SELECT user_id FROM profiles WHERE is_visible=1 LIMIT 1000")->fetchAll(PDO::FETCH_COLUMN) as $id) {
        $out .= '<url><loc>' . e(APP_URL . '/profile/' . (int) $id) . '</loc></url>';
    }
    echo $out . '</urlset>';
    exit;
});

// Remplissage de la base via le web (plan gratuit Render : pas d'accès Shell).
// Protégé par un jeton : ne fonctionne que si la variable d'env SEED_TOKEN est
// définie ET fournie dans l'URL (?token=...). Idempotent (repart d'une base propre).
Router::get('/setup/seed', static function (): never {
    header('Content-Type: text/plain; charset=UTF-8');
    $expected = (string) env('SEED_TOKEN', '');
    $given = (string) ($_GET['token'] ?? '');
    if ($expected === '' || !hash_equals($expected, $given)) {
        http_response_code(403);
        exit("403 — jeton invalide, ou variable SEED_TOKEN non definie sur le serveur.\n");
    }
    // Le seed peut durer ~2 min (génération des CV .docx) : on lève les limites
    // et on pousse la sortie au fil de l'eau pour ne pas être coupé par le proxy.
    @set_time_limit(0);
    @ini_set('max_execution_time', '0');
    while (ob_get_level() > 0) { ob_end_flush(); }
    ob_implicit_flush(true);
    define('SEED_WEB', true);
    require base_path('scripts/seed.php');
    echo "\n== Termine. Pense a SUPPRIMER la variable SEED_TOKEN pour desactiver cette route. ==\n";
    exit;
});

Router::get('/', 'PageController@home');
Router::get('/about', 'PageController@about');
Router::get('/a-propos', 'PageController@about');
Router::get('/contact', 'PageController@contact');
Router::post('/contact', 'PageController@handleContactForm');
Router::get('/cgu', 'PageController@cgu');
Router::get('/privacy', 'PageController@privacy');
Router::get('/legal', 'PageController@legal');
Router::get('/services', 'PageController@services');
Router::get('/emplois', 'PageController@emplois');
Router::get('/pricing', 'SubscriptionController@showPlans');
Router::get('/devise/{code}', static function (string $code): never {
    CurrencyService::set($code);
    $ref = $_SERVER['HTTP_REFERER'] ?? '/pricing';
    redirect(str_starts_with($ref, APP_URL) ? $ref : '/pricing');
});
Router::get('/langue/{code}', static function (string $code): never {
    Lang::set($code);
    $ref = $_SERVER['HTTP_REFERER'] ?? '/';
    redirect(str_starts_with((string) $ref, APP_URL) ? $ref : '/');
});

Router::get('/search', 'SearchController@searchAll');
Router::get('/search/profils', 'SearchController@searchProfiles');
Router::get('/search/offres', 'SearchController@searchOffers');

Router::get('/login', 'AuthController@showLogin', ['guest']);
Router::post('/login', 'AuthController@handleLogin', ['guest']);
Router::get('/register', 'AuthController@showRegister', ['guest']);
Router::post('/register', 'AuthController@handleRegister', ['guest']);
Router::get('/logout', 'AuthController@logout', ['auth']);
Router::get('/forgot-password', 'AuthController@showForgotPassword', ['guest']);
Router::post('/forgot-password', 'AuthController@handleForgotPassword', ['guest']);
Router::get('/reset-password/{token}', 'AuthController@showResetPassword', ['guest']);
Router::post('/reset-password', 'AuthController@handleResetPassword', ['guest']);

Router::get('/profile/{id}', 'ProfileController@showPublic');
Router::get('/client/profile/edit', 'ProfileController@showEditClient', ['auth', 'role:client']);
Router::get('/entreprise/profile/edit', 'ProfileController@showEditEntreprise', ['auth', 'role:entreprise']);
Router::post('/profile/update', 'ProfileController@handleUpdate', ['auth']);
Router::post('/profile/enhance', 'ProfileController@enhance', ['auth']);
Router::post('/profile/photo', 'ProfileController@uploadPhoto', ['auth']);
Router::post('/profile/cv', 'ProfileController@uploadCV', ['auth', 'role:client']);
Router::post('/profile/cv/{id}/primary', 'ProfileController@setPrimaryCV', ['auth', 'role:client']);
Router::post('/profile/cv/{id}/delete', 'ProfileController@deleteCV', ['auth', 'role:client']);

// Outils IA candidat
Router::get('/client/ia', 'AiController@tools', ['auth', 'role:client']);
Router::post('/client/ia/analyse', 'AiController@analyzeCv', ['auth', 'role:client']);
Router::post('/client/ia/optimiser', 'AiController@optimizeCv', ['auth', 'role:client']);
Router::post('/client/ia/lettre', 'AiController@coverLetter', ['auth', 'role:client']);
Router::post('/client/ia/importer-offre', 'AiController@importOffer', ['auth', 'role:client']);
Router::post('/client/ia/generer-cv', 'AiController@generateCv', ['auth', 'role:client']);
Router::post('/client/ia/document', 'AiController@document', ['auth', 'role:client']);
Router::post('/client/ia/infos-offre', 'AiController@offerInfo', ['auth', 'role:client']);

Router::get('/entreprise/verification', 'VerificationController@show', ['auth', 'role:entreprise']);
Router::post('/entreprise/verification', 'VerificationController@submit', ['auth', 'role:entreprise']);

Router::get('/entreprise/offres', 'OfferController@index', ['auth', 'role:entreprise']);
Router::get('/entreprise/offres/new', 'OfferController@create', ['auth', 'role:entreprise']);
Router::post('/entreprise/offres/ia-draft', 'OfferController@aiDraft', ['auth', 'role:entreprise']);
Router::post('/entreprise/offres', 'OfferController@store', ['auth', 'role:entreprise']);
Router::get('/entreprise/offres/{id}/edit', 'OfferController@edit', ['auth', 'role:entreprise']);
Router::post('/entreprise/offres/{id}', 'OfferController@update', ['auth', 'role:entreprise']);
Router::post('/entreprise/offres/{id}/delete', 'OfferController@destroy', ['auth', 'role:entreprise']);
Router::get('/offres/{id}', 'OfferController@showPublic');
// Le candidat (rôle client) postule aux offres publiées par les entreprises.
Router::get('/offres/{id}/postuler', 'ApplicationController@apply', ['auth', 'role:client']);

Router::get('/messages', 'MessageController@index', ['auth']);
Router::get('/messages/nouveau/{userId}', 'MessageController@start', ['auth']);
Router::get('/messages/{id}', 'MessageController@conversation', ['auth']);

// Côté candidat (client) : envoyer et gérer ses candidatures.
Router::post('/applications', 'ApplicationController@store', ['auth', 'role:client']);
Router::get('/client/candidatures', 'ApplicationController@index', ['auth', 'role:client']);
Router::post('/applications/{id}/delete', 'ApplicationController@destroy', ['auth', 'role:client']);

// Côté entreprise (recruteur) : consulter les candidatures reçues et les traiter.
Router::get('/entreprise/candidatures', 'ApplicationController@received', ['auth', 'role:entreprise']);
Router::post('/entreprise/candidatures/{id}/status', 'ApplicationController@updateStatus', ['auth', 'role:entreprise']);
Router::post('/entreprise/candidatures/{id}/analyser', 'ApplicationController@analyze', ['auth', 'role:entreprise']);

Router::get('/favoris', 'FavoriteController@index', ['auth']);
Router::post('/favorites/{id}/toggle', 'FavoriteController@toggle', ['auth']);

Router::get('/abonnement/checkout/{planId}', 'SubscriptionController@checkout', ['auth']);
Router::get('/abonnement/success', 'SubscriptionController@success', ['auth']);
Router::get('/abonnement/cancel', 'SubscriptionController@cancel', ['auth']);
Router::get('/abonnement/portail', 'SubscriptionController@portal', ['auth']);
Router::get('/abonnement', 'SubscriptionController@current', ['auth']);

// Messagerie et notifications (JSON) — routées pour fonctionner derrière router.php / Render.
Router::get('/api/messages', static function (): void {
    require base_path('api/messages.php');
}, ['auth']);
Router::post('/api/messages', static function (): void {
    require base_path('api/messages.php');
}, ['auth']);
Router::get('/api/notifications', static function (): void {
    require base_path('api/notifications.php');
}, ['auth']);
Router::post('/api/notifications', static function (): void {
    require base_path('api/notifications.php');
}, ['auth']);

Router::get('/api/saved-searches', static function (): void {
    require base_path('api/saved-searches.php');
}, ['auth']);
Router::post('/api/saved-searches', static function (): void {
    require base_path('api/saved-searches.php');
}, ['auth']);
Router::delete('/api/saved-searches/{id}', static function (string $id): void {
    $_GET['id'] = $id;
    require base_path('api/saved-searches.php');
}, ['auth']);
Router::post('/api/stripe/webhook', static function (): void {
    require base_path('api/stripe-webhook.php');
});

Router::get('/admin/verifications', 'AdminVerificationController@index', ['auth', 'role:admin']);
Router::post('/admin/verifications/{id}/review', 'AdminVerificationController@review', ['auth', 'role:admin']);

Router::get('/admin/users', 'AdminController@index', ['auth', 'role:admin']);
Router::get('/admin/users/{id}', 'AdminController@show', ['auth', 'role:admin']);
Router::get('/admin/users/{id}/suspend', 'AdminController@suspend', ['auth', 'role:admin']);
Router::get('/admin/users/{id}/restore', 'AdminController@restore', ['auth', 'role:admin']);
Router::get('/admin/users/{id}/reset-password', 'AdminController@resetPasswordTemp', ['auth', 'role:admin']);
Router::get('/admin/users/{id}/delete', 'AdminController@deleteUser', ['auth', 'role:admin']);
Router::get('/admin/subscriptions', 'AdminSubscriptionController@index', ['auth', 'role:admin']);
Router::get('/admin/subscriptions/stats', 'AdminSubscriptionController@stats', ['auth', 'role:admin']);
Router::get('/admin/subscriptions/{id}/cancel', 'AdminSubscriptionController@forceCancel', ['auth', 'role:admin']);
Router::get('/admin/subscriptions/grant/{userId}/{planId}/{days}', 'AdminSubscriptionController@grantFree', ['auth', 'role:admin']);
Router::get('/admin/categories', 'AdminCategoryController@index', ['auth', 'role:admin']);
Router::post('/admin/categories', 'AdminCategoryController@save', ['auth', 'role:admin']);
Router::get('/admin/pages/{slug}', 'AdminPageController@edit', ['auth', 'role:admin']);
Router::post('/admin/pages/{slug}', 'AdminPageController@update', ['auth', 'role:admin']);
Router::get('/admin/messages', static function (): void {
    View::render('admin/messages/index', ['title' => 'Messages'], 'admin');
}, ['auth', 'role:admin']);
Router::get('/api/admin-export', static function (): void {
    require base_path('api/admin-export.php');
}, ['auth', 'role:admin']);

Router::get('/dashboard', 'DashboardController@redirectDashboard', ['auth']);
Router::get('/client/dashboard', 'DashboardController@client', ['auth', 'role:client']);
Router::get('/entreprise/dashboard', 'DashboardController@entreprise', ['auth', 'role:entreprise']);
Router::get('/admin/dashboard', 'DashboardController@admin', ['auth', 'role:admin']);
Router::get('/admin', static function (): never {
    redirect('/admin/dashboard');
}, ['auth', 'role:admin']);