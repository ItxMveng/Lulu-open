<?php
declare(strict_types=1);

/**
 * Seed de développement / bootstrap production minimal.
 * Idempotent : ré-exécutable sans créer de doublons (basé sur l'email / le slug).
 *
 * Usage : php scripts/seed.php
 */

require_once dirname(__DIR__) . '/config/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI uniquement.' . PHP_EOL);
}

$pdo = db();

/** Crée un utilisateur s'il n'existe pas déjà (par email). Retourne l'id. */
function seed_user(PDO $pdo, string $name, string $email, string $password, string $role): int
{
    $email = strtolower(trim($email));
    $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $existing->execute(['email' => $email]);
    $found = $existing->fetch();

    if ($found) {
        echo "[SKIP] user {$email} (existe déjà)" . PHP_EOL;
        return (int) $found['id'];
    }

    $statement = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, status, subscription_status, email_verified_at, created_at, updated_at)
         VALUES (:name, :email, :hash, :role, :status, :sub, NOW(), NOW(), NOW())'
    );
    $statement->execute([
        'name' => $name,
        'email' => $email,
        'hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
        'status' => 'active',
        'sub' => 'inactive',
    ]);
    $id = (int) $pdo->lastInsertId();
    echo "[OK]   user {$email} (role={$role}, id={$id})" . PHP_EOL;

    return $id;
}

/** Crée un profil pour un utilisateur s'il n'en a pas. */
function seed_profile(PDO $pdo, int $userId, string $displayName, string $type): void
{
    $existing = $pdo->prepare('SELECT id FROM profiles WHERE user_id = :uid LIMIT 1');
    $existing->execute(['uid' => $userId]);
    if ($existing->fetch()) {
        return;
    }
    $statement = $pdo->prepare(
        'INSERT INTO profiles (user_id, type, display_name, is_visible, created_at, updated_at)
         VALUES (:uid, :type, :name, 1, NOW(), NOW())'
    );
    $statement->execute(['uid' => $userId, 'type' => $type, 'name' => $displayName]);
}

/** Crée une catégorie si le slug n'existe pas. */
function seed_category(PDO $pdo, string $name, string $slug, string $icon): void
{
    $existing = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
    $existing->execute(['slug' => $slug]);
    if ($existing->fetch()) {
        echo "[SKIP] catégorie {$slug}" . PHP_EOL;
        return;
    }
    $statement = $pdo->prepare(
        'INSERT INTO categories (name, slug, icon, created_at, updated_at) VALUES (:name, :slug, :icon, NOW(), NOW())'
    );
    $statement->execute(['name' => $name, 'slug' => $slug, 'icon' => $icon]);
    echo "[OK]   catégorie {$slug}" . PHP_EOL;
}

echo '== Utilisateurs ==' . PHP_EOL;
$adminId = seed_user($pdo, 'Administrateur', 'admin@lulu-open.local', 'Admin1234!', 'admin');
$clientId = seed_user($pdo, 'Client Démo', 'client@lulu-open.local', 'Client1234!', 'client');
$entrepriseId = seed_user($pdo, 'Entreprise Démo', 'entreprise@lulu-open.local', 'Entreprise1234!', 'entreprise');

seed_profile($pdo, $clientId, 'Client Démo', 'services');
seed_profile($pdo, $entrepriseId, 'Entreprise Démo', 'recrutement');

echo PHP_EOL . '== Catégories ==' . PHP_EOL;
seed_category($pdo, 'Développement Web', 'developpement-web', 'bi-code-slash');
seed_category($pdo, 'Design & Créa', 'design-crea', 'bi-palette');
seed_category($pdo, 'Marketing', 'marketing', 'bi-megaphone');
seed_category($pdo, 'Rédaction', 'redaction', 'bi-pencil');
seed_category($pdo, 'Data & IA', 'data-ia', 'bi-cpu');
seed_category($pdo, 'Support & Admin', 'support-admin', 'bi-headset');

echo PHP_EOL . '== Offre de démonstration ==' . PHP_EOL;
$offerExists = $pdo->prepare('SELECT id FROM offers WHERE entreprise_id = :eid LIMIT 1');
$offerExists->execute(['eid' => $entrepriseId]);
if ($offerExists->fetch()) {
    echo '[SKIP] offre de démo (existe déjà)' . PHP_EOL;
} else {
    $statement = $pdo->prepare(
        'INSERT INTO offers (entreprise_id, title, description, type, status, location, remote_ok, created_at, updated_at)
         VALUES (:eid, :title, :desc, :type, :status, :loc, :remote, NOW(), NOW())'
    );
    $statement->execute([
        'eid' => $entrepriseId,
        'title' => 'Développeur PHP / Flutter (H/F)',
        'desc' => "Nous recherchons un développeur full-stack pour rejoindre une petite équipe produit.\nStack : PHP 8, MySQL, Flutter.",
        'type' => 'emploi',
        'status' => 'active',
        'loc' => 'Remote / Paris',
        'remote' => 1,
    ]);
    echo '[OK]   offre de démo créée' . PHP_EOL;
}

echo PHP_EOL . 'Seed terminé.' . PHP_EOL;
echo 'Comptes de test (mot de passe entre parenthèses) :' . PHP_EOL;
echo '  admin@lulu-open.local (Admin1234!)' . PHP_EOL;
echo '  client@lulu-open.local (Client1234!)' . PHP_EOL;
echo '  entreprise@lulu-open.local (Entreprise1234!)' . PHP_EOL;
