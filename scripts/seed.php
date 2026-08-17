<?php
declare(strict_types=1);

/**
 * Seed de démonstration RICHE et propre.
 * - Nettoie les candidats (client) et entreprises existants.
 * - Crée 2 talents complets par catégorie (profil pro + CV .docx généré).
 * - Crée 2 entreprises vérifiées avec des offres soignées.
 * Idempotent : relançable (repart d'une base propre côté client/entreprise).
 *
 * Usage : php scripts/seed.php
 */

require_once dirname(__DIR__) . '/config/config.php';
// CLI par défaut ; exécution web autorisée uniquement via la route protégée
// /setup/seed (qui définit SEED_WEB après vérification du jeton SEED_TOKEN).
if (PHP_SAPI !== 'cli' && !defined('SEED_WEB')) { http_response_code(403); exit('CLI uniquement.' . PHP_EOL); }

$pdo = db();
$profiles = new Profile();
$cvDocs = new CvDocument();

/* ------------------------------------------------------------------ Nettoyage */
echo '== Nettoyage (candidats + entreprises) ==' . PHP_EOL;
$pdo->exec("DELETE FROM users WHERE role IN ('client', 'entreprise')"); // cascade: profils, offres, candidatures, cv, abonnements…

/* ------------------------------------------------------------------ Admin */
$adminExists = $pdo->query("SELECT id FROM users WHERE role='admin' LIMIT 1")->fetch();
if (!$adminExists) {
    $pdo->prepare("INSERT INTO users (name, email, password_hash, role, status, subscription_status, email_verified_at, created_at, updated_at) VALUES ('Administrateur', 'admin@lulu-open.local', :h, 'admin', 'active', 'inactive', NOW(), NOW(), NOW())")
        ->execute(['h' => password_hash('Admin1234!', PASSWORD_DEFAULT)]);
}

/* ------------------------------------------------------------------ Catégories */
$catRows = $pdo->query('SELECT id, name, slug FROM categories ORDER BY id')->fetchAll();

/* Compétences par slug de catégorie */
$SKILLS = [
    'developpement-web' => ['PHP', 'Laravel', 'JavaScript', 'React', 'Vue.js', 'Node.js', 'MySQL', 'API REST', 'Git'],
    'design-crea' => ['UI/UX', 'Figma', 'Photoshop', 'Illustrator', 'Design graphique', 'Branding', 'Motion design'],
    'marketing' => ['SEO', 'Google Ads', 'Marketing digital', 'Community management', 'Publicité', 'Emailing', 'Analytics'],
    'redaction' => ['Rédaction web', 'Copywriting', 'SEO', 'Traduction', 'Storytelling', 'Relecture'],
    'data-ia' => ['Python', 'Machine learning', 'Data analyse', 'SQL', 'Power BI', 'Statistiques', 'TensorFlow'],
    'support-admin' => ['Support client', 'Bureautique', 'Gestion administrative', 'Saisie de données', 'Standard téléphonique'],
    'commerce-vente' => ['Vente', 'Négociation', 'Prospection', 'Relation client', 'CRM', 'Développement commercial'],
    'comptabilite-finance' => ['Comptabilité', 'Fiscalité', 'Excel', 'Analyse financière', 'Paie', 'Reporting'],
    'ressources-humaines' => ['Recrutement', 'Paie', 'Droit social', 'Formation', 'SIRH', 'Gestion des talents'],
    'sante-social' => ['Soins infirmiers', 'Aide à la personne', 'Écoute active', 'Premiers secours', 'Accompagnement'],
    'enseignement-formation' => ['Pédagogie', 'Formation', 'E-learning', 'Animation de groupe', 'Ingénierie pédagogique'],
    'batiment-travaux' => ['Maçonnerie', 'Électricité', 'Plomberie', 'Lecture de plans', 'Sécurité chantier', 'Carrelage'],
    'artisanat' => ['Menuiserie', 'Soudure', 'Couture', 'Réparation', 'Ébénisterie', 'Ferronnerie'],
    'restauration-hotellerie' => ['Cuisine', 'Service en salle', 'Pâtisserie', 'Gestion de salle', 'Hygiène HACCP', 'Accueil'],
    'beaute-bien-etre' => ['Coiffure', 'Esthétique', 'Maquillage', 'Massage', 'Onglerie', 'Conseil beauté'],
    'transport-logistique' => ['Conduite', 'Logistique', 'Gestion de stock', 'Manutention', 'Planification', 'Livraison'],
    'juridique' => ['Droit des affaires', 'Rédaction juridique', 'Contentieux', 'Conseil juridique', 'Veille réglementaire'],
    'agriculture-environnement' => ['Agronomie', 'Maraîchage', 'Élevage', 'Développement durable', 'Gestion de projet agricole'],
];
$CERTS = ['Certification professionnelle', 'Diplôme d\'État', 'Formation certifiante', 'Attestation de compétences'];
$LANGS_EXTRA = ['Anglais', 'Espagnol', 'Arabe', 'Portugais', 'Wolof', 'Lingala', 'Swahili'];
$LOCATIONS = ['Dakar, Sénégal', 'Abidjan, Côte d\'Ivoire', 'Douala, Cameroun', 'Yaoundé, Cameroun', 'Lagos, Nigéria', 'Accra, Ghana', 'Nairobi, Kenya', 'Casablanca, Maroc', 'Kinshasa, RDC', 'Cotonou, Bénin', 'Lomé, Togo', 'Bamako, Mali', 'Ouagadougou, Burkina Faso', 'Télétravail'];
$NAMES = ['Aminata Diallo', 'Kwame Mensah', 'Fatou Ndiaye', 'Chidi Okonkwo', 'Awa Traoré', 'Jean-Baptiste Koffi', 'Mariam Cissé', 'Emeka Obi', 'Nadia El Amrani', 'Thabo Nkosi', 'Grace Wanjiru', 'Ibrahim Sow', 'Leïla Benali', 'Samuel Mbeki', 'Aïcha Koné', 'David Owusu', 'Zineb Tazi', 'Kofi Asante', 'Ngozi Eze', 'Moussa Camara', 'Rania Haddad', 'Tunde Bakare', 'Sophie Mballa', 'Yao Kouassi', 'Amina Yusuf', 'Pierre Nguema', 'Halima Diarra', 'Blessing Adeyemi', 'Omar Fall', 'Céline Abena', 'Mamadou Baldé', 'Esther Achieng', 'Rachid Alaoui', 'Fatoumata Sylla', 'Daniel Osei', 'Khadija Bello', 'Serge Mbarga', 'Aya Kouadio', 'Hassan Toure', 'Linda Mwangi'];

/* Génère un CV Markdown à partir des données du talent. */
function build_cv(string $name, string $title, array $skills, array $exp, string $bio, array $langs): string
{
    $cv = "# {$name}\n*{$title}*\n\n## Profil\n{$bio}\n\n## Compétences\n";
    foreach ($skills as $s) { $cv .= "- {$s}\n"; }
    $cv .= "\n## Expériences\n";
    foreach ($exp as $e) { $cv .= "**{$e[0]}** *({$e[1]})*\n- {$e[2]}\n\n"; }
    $cv .= "## Langues\n" . implode(', ', $langs) . "\n";
    return $cv;
}

function make_user(PDO $pdo, string $name, string $email, string $role): int
{
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status, subscription_status, email_verified_at, verification_status, created_at, updated_at) VALUES (:n, :e, :h, :r, :active, :active, NOW(), :vs, NOW(), NOW())');
    $stmt->execute([
        'n' => $name, 'e' => $email, 'h' => password_hash($role === 'entreprise' ? 'Entreprise1234!' : 'Talent1234!', PASSWORD_DEFAULT),
        'r' => $role, 'active' => 'active', 'vs' => $role === 'entreprise' ? 'verified' : null,
    ]);
    return (int) $pdo->lastInsertId();
}

/* ------------------------------------------------------------------ Talents */
echo '== Talents (2 par catégorie) ==' . PHP_EOL;
$nameIdx = 0; $talentIds = []; $emailN = 1;
$cvDir = UPLOADS_PATH . DIRECTORY_SEPARATOR . 'cv';
if (!is_dir($cvDir)) { mkdir($cvDir, 0775, true); }

foreach ($catRows as $cat) {
    $slug = (string) $cat['slug'];
    $skillPool = $SKILLS[$slug] ?? ['Polyvalence', 'Rigueur', 'Autonomie'];
    for ($k = 0; $k < 2; $k++) {
        $name = $NAMES[$nameIdx % count($NAMES)]; $nameIdx++;
        $years = 3 + ($nameIdx % 8);
        $title = $cat['name'] . ' — ' . $skillPool[0];
        $skills = array_slice($skillPool, 0, 5 + ($k));
        $langs = ['Français', $LANGS_EXTRA[$nameIdx % count($LANGS_EXTRA)]];
        $location = $LOCATIONS[$nameIdx % count($LOCATIONS)];
        $rate = 15 + (($nameIdx * 7) % 55);
        $bio = "Professionnel(le) du domaine « {$cat['name']} » avec {$years} ans d'expérience. Spécialisé(e) en " . implode(', ', array_slice($skills, 0, 3)) . ". Reconnu(e) pour la qualité du travail livré, le sens du détail et l'engagement client.";
        $portfolio = ['https://portfolio.example.com/' . strtolower(str_replace(' ', '', $name)), 'Projet phare : refonte complète pour un client du secteur ' . $cat['name'], 'Mission réussie avec résultats mesurables (satisfaction client élevée)'];
        $certs = [$CERTS[$nameIdx % count($CERTS)] . ' en ' . $cat['name']];
        $exp = [
            [$skillPool[0] . ' senior', ($years) . ' ans', 'Pilotage de projets et livraison de solutions ' . strtolower($cat['name']) . ' pour divers clients.'],
            [$skillPool[0] . ' junior', '2 ans', 'Montée en compétences et contribution à des projets variés.'],
        ];

        $email = 'talent' . $emailN . '@lulu-open.local'; $emailN++;
        $uid = make_user($pdo, $name, $email, 'client');
        $talentIds[] = $uid;

        $profiles->save($uid, [
            'type' => 'services', 'display_name' => $name, 'bio' => $bio, 'location' => $location,
            'categories' => [$cat['name']], 'skills' => $skills, 'languages' => $langs,
            'hourly_rate' => $rate, 'availability' => 'Disponible', 'portfolio' => $portfolio,
            'certifications' => $certs, 'is_visible' => 1,
        ]);

        // CV .docx généré
        $cvMd = build_cv($name, $title, $skills, $exp, $bio, $langs);
        $rel = 'uploads/cv/seed_' . bin2hex(random_bytes(6)) . '.docx';
        file_put_contents(base_path($rel), DocumentRenderer::toDocx($cvMd, 'CV — ' . $name));
        $cvDocs->add($uid, $rel, 'CV_' . str_replace(' ', '_', $name) . '.docx', true);
        echo '.'; // progression (garde la connexion active côté web pendant la génération des .docx)
    }
}
echo PHP_EOL . '  ' . count($talentIds) . ' talents créés.' . PHP_EOL;

/* ------------------------------------------------------------------ Entreprises */
echo '== Entreprises (2) + offres ==' . PHP_EOL;
$companies = [
    ['AfriTech Solutions', 'entreprise@lulu-open.local', 'Abidjan, Côte d\'Ivoire', 'AfriTech Solutions accompagne la transformation digitale des entreprises africaines : développement logiciel, data et conseil. Une équipe passionnée, une culture de l\'excellence et de l\'impact.', ['Développement Web', 'Data & IA'], [
        ['title' => 'Développeur Full-Stack (H/F)', 'type' => 'emploi', 'ct' => 'CDI', 'loc' => 'Abidjan / Télétravail', 'remote' => 1, 'smin' => 9000, 'smax' => 15000, 'skills' => ['PHP', 'Laravel', 'React', 'MySQL'], 'desc' => "Rejoignez notre équipe produit pour bâtir des applications web performantes au service des entreprises africaines.\n\nVos missions : développer et maintenir nos plateformes, concevoir des API robustes, collaborer avec les designers et la data."],
        ['title' => 'Data Analyst (H/F)', 'type' => 'emploi', 'ct' => 'CDI', 'loc' => 'Abidjan', 'remote' => 0, 'smin' => 8000, 'smax' => 13000, 'skills' => ['Python', 'SQL', 'Power BI'], 'desc' => "Transformez la donnée en décisions. Vous analyserez les données de nos clients et construirez des tableaux de bord à fort impact."],
        ['title' => 'Chef de projet digital (mission)', 'type' => 'mission', 'ct' => 'Freelance', 'loc' => 'Télétravail', 'remote' => 1, 'smin' => null, 'smax' => null, 'skills' => ['Gestion de projet', 'Agile'], 'desc' => "Mission de 4 mois pour piloter le déploiement d'une solution SaaS chez un grand compte."],
    ]],
    ['Sahel Talents Group', 'recruteur@lulu-open.local', 'Dakar, Sénégal', 'Sahel Talents Group est un cabinet de recrutement et de services qui connecte les meilleurs profils aux entreprises en croissance à travers l\'Afrique de l\'Ouest.', ['Ressources Humaines', 'Commerce & Vente'], [
        ['title' => 'Commercial terrain (H/F)', 'type' => 'emploi', 'ct' => 'CDI', 'loc' => 'Dakar', 'remote' => 0, 'smin' => 6000, 'smax' => 11000, 'skills' => ['Vente', 'Prospection', 'Négociation'], 'desc' => "Développez notre portefeuille clients sur la région de Dakar. Prospection, rendez-vous, closing et fidélisation."],
        ['title' => 'Chargé de recrutement (H/F)', 'type' => 'emploi', 'ct' => 'CDD', 'loc' => 'Dakar / Hybride', 'remote' => 1, 'smin' => 7000, 'smax' => 12000, 'skills' => ['Recrutement', 'Sourcing', 'Entretien'], 'desc' => "Pilotez le cycle complet de recrutement pour nos clients : sourcing, entretiens, présentation des shortlists."],
        ['title' => 'Assistant administratif (stage)', 'type' => 'stage', 'ct' => 'Stage', 'loc' => 'Dakar', 'remote' => 0, 'smin' => null, 'smax' => null, 'skills' => ['Bureautique', 'Organisation'], 'desc' => "Stage de 6 mois en appui de l'équipe administrative et RH."],
    ]],
];

$offerIds = [];
foreach ($companies as $c) {
    $eid = make_user($pdo, $c[0], $c[1], 'entreprise');
    $profiles->save($eid, [
        'type' => 'recrutement', 'display_name' => $c[0], 'bio' => $c[3], 'location' => $c[2],
        'categories' => $c[4], 'skills' => [], 'languages' => ['Français', 'Anglais'], 'is_visible' => 1,
    ]);
    $country = trim((string) substr($c[2], (int) strrpos($c[2], ',') + 1));
    foreach ($c[5] as $o) {
        $loc = (stripos($o['loc'], $country) === false) ? $o['loc'] . ', ' . $country : $o['loc'];
        $st = $pdo->prepare('INSERT INTO offers (entreprise_id, title, description, type, status, contract_type, location, remote_ok, salary_min, salary_max, skills_required, created_at, updated_at) VALUES (:e, :t, :d, :ty, :active, :ct, :loc, :rem, :smin, :smax, :sk, NOW(), NOW())');
        $st->execute([
            'e' => $eid, 't' => $o['title'], 'd' => $o['desc'], 'ty' => $o['type'], 'active' => 'active',
            'ct' => $o['ct'], 'loc' => $loc, 'rem' => $o['remote'], 'smin' => $o['smin'], 'smax' => $o['smax'],
            'sk' => json_encode($o['skills'], JSON_UNESCAPED_UNICODE),
        ]);
        $offerIds[] = [(int) $pdo->lastInsertId(), $eid];
    }
}
echo '  ' . count($companies) . ' entreprises, ' . count($offerIds) . ' offres.' . PHP_EOL;

/* ------------------------------------------------------------------ Candidatures */
echo '== Candidatures de démonstration ==' . PHP_EOL;
$appStmt = $pdo->prepare('INSERT INTO applications (applicant_id, entreprise_id, offer_id, status, cover_letter, created_at, updated_at) VALUES (:a, :e, :o, :s, :c, NOW(), NOW())');
$statuses = ['en_attente', 'vue', 'entretien'];
for ($i = 0; $i < 6 && $i < count($talentIds); $i++) {
    [$oid, $eid] = $offerIds[$i % count($offerIds)];
    $appStmt->execute(['a' => $talentIds[$i], 'e' => $eid, 'o' => $oid, 's' => $statuses[$i % 3], 'c' => 'Bonjour, je suis vivement intéressé(e) par cette opportunité qui correspond à mon profil et à mes ambitions.']);
}

echo PHP_EOL . 'Seed terminé.' . PHP_EOL;
echo 'Admin    : admin@lulu-open.local / Admin1234!' . PHP_EOL;
echo 'Talent   : talent1@lulu-open.local … talent' . ($emailN - 1) . '@lulu-open.local / Talent1234!' . PHP_EOL;
echo 'Entreprise : entreprise@lulu-open.local & recruteur@lulu-open.local / Entreprise1234!' . PHP_EOL;
