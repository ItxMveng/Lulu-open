<?php
declare(strict_types=1);

/**
 * Seed de développement — jeu de données de démonstration réaliste.
 * Idempotent : ré-exécutable sans doublons (basé sur l'email / le slug / le titre).
 *
 * Usage : php scripts/seed.php
 */

require_once dirname(__DIR__) . '/config/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI uniquement.' . PHP_EOL);
}

$pdo = db();
$profiles = new Profile();

function seed_user(PDO $pdo, string $name, string $email, string $password, string $role): int
{
    $email = strtolower(trim($email));
    $existing = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $existing->execute(['email' => $email]);
    if ($found = $existing->fetch()) {
        return (int) $found['id'];
    }
    $statement = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, status, subscription_status, email_verified_at, created_at, updated_at)
         VALUES (:name, :email, :hash, :role, :status, :sub, NOW(), NOW(), NOW())'
    );
    $statement->execute([
        'name' => $name, 'email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role, 'status' => 'active', 'sub' => 'inactive',
    ]);
    echo "[OK]   user {$email}" . PHP_EOL;
    return (int) $pdo->lastInsertId();
}

function seed_category(PDO $pdo, string $name, string $slug, string $icon): void
{
    $existing = $pdo->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
    $existing->execute(['slug' => $slug]);
    if ($existing->fetch()) {
        return;
    }
    $pdo->prepare('INSERT INTO categories (name, slug, icon, created_at, updated_at) VALUES (:n, :s, :i, NOW(), NOW())')
        ->execute(['n' => $name, 's' => $slug, 'i' => $icon]);
}

function seed_offer(PDO $pdo, int $entrepriseId, array $o): int
{
    $existing = $pdo->prepare('SELECT id FROM offers WHERE entreprise_id = :e AND title = :t LIMIT 1');
    $existing->execute(['e' => $entrepriseId, 't' => $o['title']]);
    if ($found = $existing->fetch()) {
        return (int) $found['id'];
    }
    $statement = $pdo->prepare(
        'INSERT INTO offers (entreprise_id, title, description, type, status, contract_type, location, remote_ok, salary_min, salary_max, skills_required, created_at, updated_at)
         VALUES (:e, :t, :d, :ty, :st, :ct, :loc, :rem, :smin, :smax, :sk, NOW(), NOW())'
    );
    $statement->execute([
        'e' => $entrepriseId, 't' => $o['title'], 'd' => $o['description'], 'ty' => $o['type'], 'st' => 'active',
        'ct' => $o['contract_type'], 'loc' => $o['location'], 'rem' => $o['remote_ok'] ? 1 : 0,
        'smin' => $o['salary_min'] ?? null, 'smax' => $o['salary_max'] ?? null,
        'sk' => json_encode($o['skills'] ?? [], JSON_UNESCAPED_UNICODE),
    ]);
    return (int) $pdo->lastInsertId();
}

function seed_application(PDO $pdo, int $applicantId, int $entrepriseId, int $offerId, string $status): void
{
    $existing = $pdo->prepare('SELECT id FROM applications WHERE applicant_id = :a AND offer_id = :o LIMIT 1');
    $existing->execute(['a' => $applicantId, 'o' => $offerId]);
    if ($existing->fetch()) {
        return;
    }
    $pdo->prepare(
        'INSERT INTO applications (applicant_id, entreprise_id, offer_id, status, cover_letter, created_at, updated_at)
         VALUES (:a, :e, :o, :s, :c, NOW(), NOW())'
    )->execute(['a' => $applicantId, 'e' => $entrepriseId, 'o' => $offerId, 's' => $status, 'c' => 'Candidature de démonstration.']);
}

echo '== Comptes principaux ==' . PHP_EOL;
seed_user($pdo, 'Administrateur', 'admin@lulu-open.local', 'Admin1234!', 'admin');

echo '== Catégories ==' . PHP_EOL;
$cats = [
    // Originales (ne pas renommer : référencées par les profils existants)
    ['Développement Web', 'developpement-web', 'bi-code-slash'],
    ['Design & Créa', 'design-crea', 'bi-palette'],
    ['Marketing', 'marketing', 'bi-megaphone'],
    ['Rédaction', 'redaction', 'bi-pencil'],
    ['Data & IA', 'data-ia', 'bi-cpu'],
    ['Support & Admin', 'support-admin', 'bi-headset'],
    // Nouveaux secteurs (tous métiers)
    ['Commerce & Vente', 'commerce-vente', 'bi-bag'],
    ['Comptabilité & Finance', 'comptabilite-finance', 'bi-calculator'],
    ['Ressources Humaines', 'ressources-humaines', 'bi-people'],
    ['Santé & Social', 'sante-social', 'bi-heart-pulse'],
    ['Enseignement & Formation', 'enseignement-formation', 'bi-mortarboard'],
    ['Bâtiment & Travaux', 'batiment-travaux', 'bi-hammer'],
    ['Artisanat & Métiers manuels', 'artisanat', 'bi-tools'],
    ['Restauration & Hôtellerie', 'restauration-hotellerie', 'bi-cup-hot'],
    ['Beauté & Bien-être', 'beaute-bien-etre', 'bi-scissors'],
    ['Transport & Logistique', 'transport-logistique', 'bi-truck'],
    ['Juridique', 'juridique', 'bi-bank'],
    ['Agriculture & Environnement', 'agriculture-environnement', 'bi-tree'],
];
foreach ($cats as $c) { seed_category($pdo, $c[0], $c[1], $c[2]); }

echo '== Candidats (talents) ==' . PHP_EOL;
$candidates = [
    ['Camille Martin', 'Développement Web', ['PHP', 'Laravel', 'MySQL', 'JavaScript'], ['Français', 'Anglais'], 45, 'Paris', 'Développeuse back-end avec 6 ans d\'expérience sur des applications web à fort trafic.'],
    ['Yanis Benali', 'Développement Web', ['Flutter', 'Dart', 'Firebase', 'TypeScript'], ['Français', 'Anglais', 'Arabe'], 50, 'Lyon', 'Développeur mobile Flutter, passionné par les belles interfaces et la performance.'],
    ['Sophie Nguyen', 'Design & Créa', ['UI/UX', 'Figma', 'Photoshop', 'Illustrator'], ['Français', 'Anglais'], 40, 'Remote', 'Designer produit orientée UX, du wireframe au design system.'],
    ['Marc Dubois', 'Data & IA', ['Python', 'Data analyse', 'Machine learning', 'SQL'], ['Français', 'Anglais', 'Allemand'], 60, 'Remote', 'Data scientist, modèles prédictifs et visualisation de données.'],
    ['Léa Rossi', 'Marketing', ['SEO', 'Google Ads', 'Marketing digital', 'Community management'], ['Français', 'Italien', 'Anglais'], 38, 'Marseille', 'Spécialiste acquisition et growth marketing pour startups.'],
    ['Thomas Girard', 'Rédaction', ['Rédaction', 'SEO', 'Traduction'], ['Français', 'Anglais', 'Espagnol'], 30, 'Bordeaux', 'Rédacteur web et concepteur-rédacteur, contenus SEO et éditoriaux.'],
];
$candidateIds = [];
foreach ($candidates as $i => $c) {
    $email = 'candidat' . ($i + 1) . '@lulu-open.local';
    $uid = seed_user($pdo, $c[0], $email, 'Candidat1234!', 'client');
    $candidateIds[] = $uid;
    $profiles->save($uid, [
        'type' => 'services', 'display_name' => $c[0], 'bio' => $c[6], 'location' => $c[5],
        'categories' => [$c[1]], 'skills' => $c[2], 'languages' => $c[3],
        'hourly_rate' => $c[4], 'availability' => 'Disponible', 'is_visible' => 1,
    ]);
}
// Compte de test principal candidat
$clientId = seed_user($pdo, 'Client Démo', 'client@lulu-open.local', 'Client1234!', 'client');
$profiles->save($clientId, [
    'type' => 'services', 'display_name' => 'Client Démo', 'bio' => 'Profil de démonstration.', 'location' => 'Paris',
    'categories' => ['Développement Web'], 'skills' => ['PHP', 'Flutter', 'MySQL'], 'languages' => ['Français', 'Anglais'],
    'hourly_rate' => 45, 'availability' => 'Immédiate', 'is_visible' => 1,
]);

echo '== Entreprises & offres ==' . PHP_EOL;
$entreprises = [
    ['TechNova', 'technova@lulu-open.local', 'Éditeur de logiciels SaaS B2B en pleine croissance.', 'Paris'],
    ['PixelStudio', 'pixelstudio@lulu-open.local', 'Studio de design et de création digitale.', 'Lyon'],
    ['DataForge', 'dataforge@lulu-open.local', 'Cabinet de conseil en data et intelligence artificielle.', 'Remote'],
];
$offersByCompany = [
    [
        ['title' => 'Développeur Back-end PHP (H/F)', 'type' => 'emploi', 'contract_type' => 'CDI', 'location' => 'Paris', 'remote_ok' => true, 'salary_min' => 42000, 'salary_max' => 55000, 'skills' => ['PHP', 'Laravel', 'MySQL'], 'description' => "Rejoignez notre équipe produit pour construire notre plateforme SaaS.\nStack : PHP 8, Laravel, MySQL, Docker."],
        ['title' => 'Développeur Flutter (H/F)', 'type' => 'emploi', 'contract_type' => 'CDI', 'location' => 'Remote', 'remote_ok' => true, 'salary_min' => 45000, 'salary_max' => 58000, 'skills' => ['Flutter', 'Dart'], 'description' => "Nous recherchons un développeur mobile Flutter pour notre application grand public."],
    ],
    [
        ['title' => 'Designer UI/UX (H/F)', 'type' => 'emploi', 'contract_type' => 'CDI', 'location' => 'Lyon', 'remote_ok' => true, 'salary_min' => 38000, 'salary_max' => 48000, 'skills' => ['UI/UX', 'Figma'], 'description' => "Concevez des expériences digitales mémorables pour nos clients."],
        ['title' => 'Motion Designer (mission)', 'type' => 'mission', 'contract_type' => 'Freelance', 'location' => 'Remote', 'remote_ok' => true, 'salary_min' => null, 'salary_max' => null, 'skills' => ['Illustrator', 'Photoshop'], 'description' => "Mission freelance de 3 mois pour la production de contenus animés."],
    ],
    [
        ['title' => 'Data Scientist (H/F)', 'type' => 'emploi', 'contract_type' => 'CDI', 'location' => 'Remote', 'remote_ok' => true, 'salary_min' => 50000, 'salary_max' => 70000, 'skills' => ['Python', 'Machine learning', 'SQL'], 'description' => "Développez des modèles prédictifs pour nos clients grands comptes."],
        ['title' => 'Stage Data Analyst', 'type' => 'stage', 'contract_type' => 'Stage', 'location' => 'Paris', 'remote_ok' => false, 'salary_min' => null, 'salary_max' => null, 'skills' => ['Data analyse', 'SQL'], 'description' => "Stage de 6 mois au sein de notre équipe data."],
    ],
];
$allOffers = [];
foreach ($entreprises as $idx => $e) {
    $eid = seed_user($pdo, $e[0], $e[1], 'Entreprise1234!', 'entreprise');
    $profiles->save($eid, [
        'type' => 'recrutement', 'display_name' => $e[0], 'bio' => $e[2], 'location' => $e[3],
        'categories' => [], 'skills' => [], 'languages' => ['Français', 'Anglais'], 'is_visible' => 1,
    ]);
    foreach ($offersByCompany[$idx] as $o) {
        $allOffers[] = [seed_offer($pdo, $eid, $o), $eid];
    }
}
// Compte de test principal entreprise + son offre
$entrepriseId = seed_user($pdo, 'Entreprise Démo', 'entreprise@lulu-open.local', 'Entreprise1234!', 'entreprise');
$profiles->save($entrepriseId, ['type' => 'recrutement', 'display_name' => 'Entreprise Démo', 'bio' => 'Entreprise de démonstration.', 'location' => 'Paris', 'categories' => [], 'skills' => [], 'languages' => ['Français'], 'is_visible' => 1]);
$demoOffer = seed_offer($pdo, $entrepriseId, ['title' => 'Développeur PHP / Flutter (H/F)', 'type' => 'emploi', 'contract_type' => 'CDI', 'location' => 'Remote / Paris', 'remote_ok' => true, 'salary_min' => 40000, 'salary_max' => 55000, 'skills' => ['PHP', 'Flutter'], 'description' => "Poste full-stack pour une petite équipe produit."]);

// Les entreprises de démonstration sont considérées comme vérifiées.
$pdo->query("UPDATE users SET verification_status = 'verified' WHERE role = 'entreprise'");

echo '== Candidatures ==' . PHP_EOL;
if (!empty($allOffers) && count($candidateIds) >= 4) {
    seed_application($pdo, $candidateIds[0], $allOffers[0][1], $allOffers[0][0], 'en_attente');
    seed_application($pdo, $candidateIds[1], $allOffers[1][1], $allOffers[1][0], 'vue');
    seed_application($pdo, $candidateIds[2], $allOffers[2][1], $allOffers[2][0], 'entretien');
    seed_application($pdo, $candidateIds[3], $allOffers[4][1], $allOffers[4][0], 'en_attente');
}
seed_application($pdo, $clientId, $entrepriseId, $demoOffer, 'en_attente');

echo PHP_EOL . 'Seed terminé.' . PHP_EOL;
echo 'Comptes : admin@ / client@ / entreprise@lulu-open.local (mdp Admin1234! / Client1234! / Entreprise1234!)' . PHP_EOL;
echo 'Candidats : candidat1..6@lulu-open.local (Candidat1234!) — Entreprises : technova@, pixelstudio@, dataforge@ (Entreprise1234!)' . PHP_EOL;
