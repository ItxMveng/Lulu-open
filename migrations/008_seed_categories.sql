-- Catégories métiers de référence.
-- Indispensable : sans ces lignes, la table `categories` est vide sur un
-- environnement fraîchement migré (ex. base de prod), et la page d'édition de
-- profil n'affiche aucune puce -> « Choisissez au moins un domaine ».
-- Idempotent : slug est UNIQUE, donc ON DUPLICATE KEY UPDATE réactualise sans doublon.
INSERT INTO categories (name, slug, icon, created_at, updated_at) VALUES
    ('Développement Web',            'developpement-web',        'bi-code-slash',  NOW(), NOW()),
    ('Design & Créa',                'design-crea',              'bi-palette',     NOW(), NOW()),
    ('Marketing',                    'marketing',                'bi-megaphone',   NOW(), NOW()),
    ('Rédaction',                    'redaction',                'bi-pencil',      NOW(), NOW()),
    ('Data & IA',                    'data-ia',                  'bi-cpu',         NOW(), NOW()),
    ('Support & Admin',              'support-admin',            'bi-headset',     NOW(), NOW()),
    ('Commerce & Vente',             'commerce-vente',           'bi-bag',         NOW(), NOW()),
    ('Comptabilité & Finance',       'comptabilite-finance',     'bi-calculator',  NOW(), NOW()),
    ('Ressources Humaines',          'ressources-humaines',      'bi-people',      NOW(), NOW()),
    ('Santé & Social',               'sante-social',             'bi-heart-pulse', NOW(), NOW()),
    ('Enseignement & Formation',     'enseignement-formation',   'bi-mortarboard', NOW(), NOW()),
    ('Bâtiment & Travaux',           'batiment-travaux',         'bi-hammer',      NOW(), NOW()),
    ('Artisanat & Métiers manuels',  'artisanat',                'bi-tools',       NOW(), NOW()),
    ('Restauration & Hôtellerie',    'restauration-hotellerie',  'bi-cup-hot',     NOW(), NOW()),
    ('Beauté & Bien-être',           'beaute-bien-etre',         'bi-scissors',    NOW(), NOW()),
    ('Transport & Logistique',       'transport-logistique',     'bi-truck',       NOW(), NOW()),
    ('Juridique',                    'juridique',                'bi-bank',        NOW(), NOW()),
    ('Agriculture & Environnement',  'agriculture-environnement','bi-tree',        NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), icon = VALUES(icon), updated_at = NOW();
