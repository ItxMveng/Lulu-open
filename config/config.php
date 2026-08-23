<?php
declare(strict_types=1);

if (defined('APP_BOOTSTRAPPED')) {
    return;
}

define('APP_BOOTSTRAPPED', true);
define('BASE_PATH', dirname(__DIR__));

function base_path(string $path = ''): string
{
    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    if ($normalized === '') {
        return BASE_PATH;
    }

    return BASE_PATH . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
}

function load_environment_file(string $filePath): void
{
    if (!is_file($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($name, $_ENV) && !array_key_exists($name, $_SERVER)) {
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv(sprintf('%s=%s', $name, $value));
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    $normalized = strtolower((string) $value);

    return match ($normalized) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

function env_bool(string $key, bool $default = false): bool
{
    $value = env($key, $default);

    if (is_bool($value)) {
        return $value;
    }

    return filter_var((string) $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

function normalize_base_uri(?string $uri): string
{
    $uri = trim((string) $uri);
    if ($uri === '' || $uri === '/') {
        return '';
    }

    return '/' . trim($uri, '/');
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
        // Derrière un proxy (Render, Cloudflare…), le TLS est terminé en amont.
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
}

load_environment_file(BASE_PATH . DIRECTORY_SEPARATOR . '.env');

define('APP_NAME', (string) env('APP_NAME', 'LULU-OPEN'));
define('APP_ENV', (string) env('APP_ENV', 'development'));
define('APP_DEBUG', env_bool('APP_DEBUG', APP_ENV !== 'production'));
define('APP_URL', rtrim((string) env('APP_URL', 'http://localhost/lulu'), '/'));
define('APP_VERSION', '2.0.0');
define('APP_KEY', (string) env('APP_KEY', 'change-me'));
define('APP_LOCALE', (string) env('APP_LOCALE', 'fr_FR'));
define('APP_BASE_URI', normalize_base_uri((string) parse_url(APP_URL, PHP_URL_PATH)));
define('CONFIG_PATH', base_path('config'));
define('LOG_PATH', base_path('logs'));
define('UPLOADS_PATH', base_path('uploads'));
// Bundle de certificats CA pour cURL/HTTPS (indispensable sous Windows/WAMP).
define('CA_BUNDLE', is_file(base_path('config/cacert.pem')) ? base_path('config/cacert.pem') : null);

ini_set('default_charset', 'UTF-8');
ini_set('output_buffering', '4096');

if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0775, true);
}

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_name('lulu_open_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => is_https(),
        'samesite' => 'Lax',
        'path' => APP_BASE_URI === '' ? '/' : APP_BASE_URI . '/',
    ]);
    session_start();
}

if (!ob_get_level()) {
    ob_start();
}

$composerAutoload = base_path('vendor/autoload.php');
if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(static function (string $class): void {
    $directories = [
        base_path('core'),
        base_path('controllers'),
        base_path('models'),
        base_path('includes'),
        base_path('includes/helpers'),
        base_path('includes/middleware'),
        base_path('includes/ai'),
        base_path('includes/stripe'),
        base_path('config'),
    ];

    foreach ($directories as $directory) {
        $candidate = $directory . DIRECTORY_SEPARATOR . $class . '.php';
        if (is_file($candidate)) {
            require_once $candidate;
            return;
        }
    }
});

require_once base_path('config/db.php');

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $baseUri = APP_BASE_URI;

    if ($baseUri !== '' && str_starts_with($path, $baseUri)) {
        $path = substr($path, strlen($baseUri)) ?: '/';
    }

    $path = '/' . trim($path, '/');

    return $path === '//' ? '/' : (rtrim($path, '/') ?: '/');
}

function url(string $path = ''): string
{
    $base = APP_URL;
    $path = trim($path);

    if ($path === '') {
        return $base;
    }

    return $base . '/' . ltrim($path, '/');
}

function redirect(string $path, int $status = 302): never
{
    if ($status === 301) {
        http_response_code(301);
    }
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

/** Transforme un texte en slug URL (sans accents, minuscules, tirets). */
function slugify(string $text): string
{
    $text = trim($text);
    if (function_exists('transliterator_transliterate')) {
        $t = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
        if (is_string($t)) { $text = $t; }
    }
    $text = (string) preg_replace('~[^a-zA-Z0-9]+~', '-', $text);
    $text = strtolower(trim($text, '-'));
    return $text !== '' ? $text : 'offre';
}

/** URL publique canonique d'une offre : /jobs/{slug}-{id}. */
function offer_url(array $offer): string
{
    $id = (int) ($offer['id'] ?? 0);
    return url('/jobs/' . slugify((string) ($offer['title'] ?? 'offre')) . '-' . $id);
}

/**
 * Informations légales. L'identité de l'éditeur provient de variables
 * d'environnement (à renseigner par le propriétaire — JAMAIS inventées).
 * Les hébergeurs sont connus et renseignés.
 */
function legal_info(): array
{
    $contact = (string) env('MAIL_FROM_ADDRESS', 'contact@lulu-open.com');
    return [
        'editor_name'          => (string) env('LEGAL_EDITOR_NAME', ''),
        'editor_status'        => (string) env('LEGAL_EDITOR_STATUS', ''),   // ex: SARL, auto-entrepreneur, association…
        'editor_capital'       => (string) env('LEGAL_EDITOR_CAPITAL', ''),  // ex: 1 000 €
        'editor_address'       => (string) env('LEGAL_EDITOR_ADDRESS', ''),
        'editor_reg'           => (string) env('LEGAL_EDITOR_REG', ''),      // RCS / SIRET / immatriculation
        'editor_vat'           => (string) env('LEGAL_EDITOR_VAT', ''),      // TVA intracom (optionnel)
        'publication_director' => (string) env('LEGAL_PUBLICATION_DIRECTOR', ''),
        'contact_email'        => $contact,
        'dpo_email'            => (string) env('LEGAL_DPO_EMAIL', $contact),
        'country'              => (string) env('LEGAL_COUNTRY', ''),         // pays / droit applicable
        // Hébergeurs réellement utilisés (voir DEPLOYMENT.md)
        'host_app'             => 'Render, Inc. (render.com) — San Francisco, Californie, États-Unis',
        'host_db'              => 'Clever Cloud SAS (clever-cloud.com) — Nantes, France',
        'host_files'           => 'Cloudflare, Inc. (cloudflare.com) — San Francisco, Californie, États-Unis (stockage objet R2)',
    ];
}

/** Un champ légal est-il renseigné ? (sinon on affiche « à compléter » proprement). */
function legal_field(string $value): string
{
    $value = trim($value);
    return $value !== '' ? e($value) : '<span class="text-warning">[à compléter par l\'éditeur]</span>';
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash(string $message, string $type = 'info'): void
{
    $_SESSION['_flash'][] = ['message' => $message, 'type' => $type];
}

function get_flash(): array
{
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);

    return is_array($messages) ? $messages : [];
}

function store_old_input(array $input): void
{
    $_SESSION['_old'] = $input;
}

function old(string $key, mixed $default = ''): mixed
{
    $oldInput = $_SESSION['_old'] ?? [];
    return $oldInput[$key] ?? $default;
}

function clear_old_input(): void
{
    unset($_SESSION['_old']);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): void
{
    $submittedToken = $token ?? ($_POST['_csrf_token'] ?? null);
    $currentToken = $_SESSION['_csrf_token'] ?? '';

    if (!is_string($submittedToken) || $submittedToken === '' || !hash_equals((string) $currentToken, $submittedToken)) {
        http_response_code(403);
        throw new RuntimeException('CSRF token invalide.');
    }
}

function is_auth(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']) && is_numeric($_SESSION['user_id']);
}

function current_user_id(): ?int
{
    return is_auth() ? (int) $_SESSION['user_id'] : null;
}

function current_role(): ?string
{
    return is_auth() ? (string) $_SESSION['role'] : null;
}

function auth_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function current_verification_status(): ?string
{
    return $_SESSION['user']['verification_status'] ?? null;
}

/** Une entreprise doit être vérifiée pour publier ; les autres rôles ne sont pas concernés. */
function is_verified_company(): bool
{
    if (current_role() !== 'entreprise') {
        return true;
    }
    return current_verification_status() === 'verified';
}

function dashboard_path_for_role(?string $role): string
{
    return match ($role) {
        'admin' => '/admin/dashboard',
        'entreprise' => '/entreprise/dashboard',
        'client' => '/client/dashboard',
        default => '/',
    };
}

/** Traduit une chaîne (FR par défaut, EN si la langue active est l'anglais). */
function t(string $fr): string
{
    return Lang::t($fr);
}

/** Formate un montant (stocké en EUR) dans la devise du visiteur. */
function money(float $amountEur): string
{
    return CurrencyService::format($amountEur);
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function abort(int $status, string $message = ''): never
{
    http_response_code($status);

    if (class_exists('ErrorHandler')) {
        ErrorHandler::renderHttpError($status, $message);
    }

    echo APP_DEBUG ? e($message !== '' ? $message : 'HTTP error') : 'Une erreur est survenue.';
    exit;
}