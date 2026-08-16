# LULU-OPEN

README d'audit technique et fonctionnel, reconstruit directement depuis le code du projet.

Etat observé: 29 mars 2026.

## Resume executif

LULU-OPEN est une plateforme web PHP/MySQL de mise en relation professionnelle. Le produit combine trois usages dans une meme application:

- marketplace de prestataires de services
- espace candidats avec CV, candidatures et assistance IA
- espace clients/recruteurs pour la recherche, les favoris et la messagerie

Le projet n'est pas un framework standard type Laravel ou Symfony. C'est un monolithe PHP maison, majoritairement server-rendered, avec une architecture hybride:

- pages publiques a la racine du projet
- vues metier directement accessibles dans `views/`
- controleurs MVC personnalisés dans `controllers/`
- endpoints AJAX/API dans `api/`
- scripts de maintenance et migrations manuelles dans `scripts/`

Au vu du code, le projet se situe entre un prototype avance et une application en phase de consolidation. Beaucoup de surfaces fonctionnelles existent deja, mais plusieurs couches cohabitent encore: ancien flux, nouveau flux, vues directes, controleurs, routeur central partiellement utilise, abonnements manuels et Stripe en parallele.

## Ce dont parle le projet

Le sujet central de LULU-OPEN est la rencontre entre offre et demande de competences professionnelles.

Le projet couvre:

- les prestations de services: artisans, freelances, consultants, profils techniques ou creatifs
- les profils candidats: CV, competences, experiences, candidatures
- les besoins clients ou recruteurs: recherche, consultation, favoris, prise de contact
- la monetisation de la visibilite et des services premium via abonnement
- une couche d'assistance IA pour analyser un CV, optimiser un CV et generer une lettre de motivation

## Profils utilisateurs pris en charge

Le code gere plusieurs types d'utilisateurs:

- `client`
- `prestataire`
- `candidat`
- `prestataire_candidat`
- `admin`

Le type `prestataire_candidat` est une particularite importante du projet: un meme utilisateur peut cumuler un profil commercial de prestataire et un profil de recherche d'emploi.

## Cartographie rapide du depot

Elements observes dans le code:

- `43` endpoints PHP dans `api/`
- `20` controleurs dans `controllers/`
- `15` modeles dans `models/`
- `87` vues PHP dans `views/`
- plusieurs scripts techniques dans `scripts/`

Le depot contient aussi:

- des ressources statiques dans `assets/`
- des dossiers d'upload dans `uploads/`
- des journaux dans `logs/`
- les dependances Composer dans `vendor/`

## Architecture reelle du projet

### 1. Point d'entree principal

Le point d'entree principal est [`index.php`](./index.php).

Ce fichier:

- lit l'URI courante
- route manuellement quelques chemins publics
- charge certains controleurs
- redirige certains profils connectes
- gere un endpoint favoris en direct
- retombe sur une page 404 personnalisee

Important: le projet contient aussi un routeur central dans `core/Router.php`, mais il n'est pas le coeur exclusif de l'application. En pratique, l'application fonctionne surtout avec un melange de:

- routage manuel dans `index.php`
- fichiers racine accessibles directement
- vues metier sous `views/...`
- API sous `api/...`

### 2. Pattern architectural observe

Le pattern dominant est un MVC pragmatique, mais non strict.

Ce qu'on observe:

- `models/` porte la logique d'acces aux donnees
- `controllers/` regroupe une partie de la logique metier
- `views/` contient les interfaces utilisateur
- `includes/` contient helpers, middleware, securite, theming, IA, Stripe, composants reutilises
- certaines pages restent cependant fortement procedurales et dialoguent directement avec la base

Conclusion: le projet est un monolithe PHP modulaire, mais pas encore completement homogenise.

### 3. Structure des repertoires

#### Racine

- `index.php`: routeur public principal
- `login.php`, `register.php`, `logout.php`: auth cote page
- `search.php`: page de recherche publique legacy/directe
- `profile.php`: wrapper vers `SearchController->profile()`
- `profile-detail.php`: ancienne page detail profil encore presente
- `services.php`, `emplois.php`: landing pages verticales
- `about.php`, `contact.php`, `cgu.php`, `privacy.php`, `legal.php`: pages publiques/statutaires
- `view-cv.php`: affichage direct d'un PDF uploadé
- `auth-handler.php`: traitement procedural login/register

#### `config/`

- `config.php`: constantes globales, session, securite, autoload, config IA, chargement DB
- `db.php`: singleton PDO maison
- `stripe.php`: configuration Stripe active
- `stripe-example.php`: exemple de configuration Stripe

#### `controllers/`

Zone MVC applicative:

- `HomeController`
- `PageController`
- `SearchController`
- `AuthController`
- `PaymentController`
- `SubscriptionController`
- `ClientController`
- `PrestataireController`
- `CandidatController`
- `DualController`
- `AdminController`
- `AdminCategoryController`
- `AdminSubscriptionController`
- `MessageController`
- `NotificationController`
- `FavoriteController`
- `ProfileController`

#### `models/`

Acces donnees et logique de persistance:

- `User`
- `Profile`
- `Subscription`
- `Message`
- `Notification`
- `Favorite`
- `Payment`
- `Category`
- `Review`
- `Admin`
- `Activity`
- `SavedSearch`
- `Plan`
- `BaseModel`

#### `views/`

Le dossier `views/` est tres riche et sert a la fois:

- de couche de presentation
- de pages directement accessibles
- de sous-applications par role

Sous-dossiers principaux:

- `views/pages`: pages publiques rendues par controleurs
- `views/auth`: interfaces d'authentification
- `views/client`: espace client/recruteur
- `views/candidat`: espace candidat
- `views/prestataire`: espace prestataire
- `views/prestataire_candidat`: espace dual
- `views/admin`: back-office
- `views/components`: composants partages
- `views/layouts`: gabarits et scripts communs

#### `api/`

Le dossier `api/` concentre les appels AJAX et les operations metier sans rendu HTML:

- messagerie
- favoris
- notifications
- categories
- stats
- admin users
- admin subscriptions
- export CSV
- upload photo
- upload CV
- portfolio
- Stripe webhook
- geolocalisation

#### `includes/`

Boite a outils transversale:

- middleware
- helpers de session
- theming
- i18n
- CSRF
- validation
- error handler
- barre de navigation / sidebars
- passerelle Stripe
- modules IA

#### `scripts/`

Scripts ad hoc de maintenance:

- migration Stripe
- creation/correction de tables d'abonnement
- cron de gestion d'expiration
- remplissage de donnees de test
- population des villes
- scripts de debug

## Pages et espaces fonctionnels

### Pages publiques

Pages ou parcours publics identifies:

- `/` ou `/home`: accueil
- `/services` ou `services.php`: vitrine pour trouver des prestataires
- `/emplois` ou `emplois.php`: vitrine pour trouver des candidats
- `/search` ou `search.php`: moteur de recherche
- `/profile/{id}` ou `profile.php?id=...`: consultation de profil
- `/about`
- `/contact`
- `/cgu`
- `/privacy`
- `/legal`
- `/login`
- `/register`

### Espace client

Fonctionnalites visibles dans `views/client/`:

- dashboard
- recherche de prestataires
- recherche de candidats
- consultation de profils
- favoris
- messagerie
- notifications
- historique d'activite
- recherches sauvegardees
- parametres
- avis

### Espace prestataire

Fonctionnalites visibles dans `views/prestataire/`:

- dashboard
- edition de profil prestataire
- messagerie
- abonnement
- checkout / gestion d'abonnement
- ajout de CV pour devenir aussi candidat

### Espace candidat

Fonctionnalites visibles dans `views/candidat/`:

- dashboard
- edition du profil/CV
- messages
- abonnement
- candidatures
- templates CV et lettres
- outils IA

### Espace prestataire + candidat

Fonctionnalites visibles dans `views/prestataire_candidat/`:

- dashboard combine
- edition de profil combine
- messagerie
- parametres
- gestion d'abonnement

### Espace admin

Fonctionnalites visibles dans `views/admin/`:

- dashboard
- gestion utilisateurs
- categories
- messages
- paiements
- abonnements
- statistiques
- plans
- dashboards Stripe
- exports
- details utilisateur

## Logique metier principale

### 1. Recherche et mise en relation

Le coeur produit repose sur la recherche de profils.

Deux axes de recherche sont prevus:

- recherche de prestataires
- recherche de candidats

Les filtres observes dans le code portent notamment sur:

- type de profil
- categorie
- localisation
- budget / tarif / salaire
- note moyenne
- tri
- abonnement actif

Le modele `Profile` centralise une bonne partie de cette logique via `searchProfiles()`.

### 2. Profils et CV

Le projet distingue deux structures metier:

- `profils_prestataires`
- `cvs`

Un utilisateur peut posseder:

- un profil prestataire
- un CV candidat
- ou les deux

Le detail profil public sait afficher:

- un prestataire
- un candidat
- un utilisateur dual

Le projet gere aussi:

- photo de profil
- categories principales et multiples
- competences
- diplomes / formations
- experiences
- langues
- portfolio prestataire
- avis / notes

### 3. Messagerie et engagement

La messagerie est un axe important du produit.

Capacites observees:

- liste de conversations
- historique conversation
- marquage lu/non lu
- suppression de message
- suppression de conversation
- piece jointe
- notifications associees
- interfaces dediees par type d'utilisateur

La logique est supportee a la fois par:

- `models/Message.php`
- `controllers/MessageController.php`
- `api/messages.php`
- plusieurs vues de chat selon le role

### 4. Favoris, activite, notifications

Le projet memorise egalement l'engagement utilisateur via:

- favoris de profils
- historique de consultation
- notifications internes
- recherches sauvegardees

Ces briques rendent la plateforme plus "marketplace" que simple annuaire.

### 5. Candidatures et assistance IA

L'espace candidat est l'une des zones les plus ambitieuses du projet.

Le fichier `views/candidat/candidatures.php` implemente notamment:

- ajout de candidature
- edition / suppression
- import de contenu d'offre via texte, URL ou fichier
- extraction de texte depuis PDF, DOC, image
- analyse critique CV versus offre
- generation d'un CV optimise
- generation de lettre de motivation

La chaine technique IA observee est la suivante:

- extraction texte PDF avec `SimplePdfExtractor`
- fallback possible via `spatie/pdf-to-text`
- OCR image via `ImageOcrExtractor`
- combinaison BDD + fichier avec `CvUtils`
- appel Mistral via `IAProvider`
- fallback regex si l'IA repond mal ou si les donnees sont faibles

Ce module est clairement l'une des parties les plus differentiantes du projet.

### 6. Abonnements et paiements

Le projet contient plusieurs systemes d'abonnement qui cohabitent encore.

#### Systeme manuel / historique

On observe un flux de demandes manuelles avec:

- `subscription_requests`
- `pricings`
- preuves de paiement uploadées
- validation admin
- activation d'abonnement apres verification

#### Systeme Stripe

On observe en parallele:

- `views/payments.php`
- `PaymentController`
- `StripeGateway`
- `api/stripe-webhook.php`
- tables `demandes_upgrade` et `paiements_stripe`

Le projet vise donc clairement une automatisation Stripe, mais la bascule n'est pas encore totalement stabilisee.

### 7. Administration et exploitation

Le back-office couvre deja beaucoup de sujets:

- vision d'ensemble des utilisateurs
- details, statut, reset password temporaire
- categories
- paiements
- abonnements
- exports CSV
- messagerie admin
- statistiques globales
- monitoring Stripe

Le projet est donc pense comme un produit exploitable, pas seulement comme un front public.

## Methodologies et choix de conception observes

Le code montre plusieurs methodologies implicites:

### Monolithe modulaire PHP

Le projet reste dans un seul depot, une seule application et une seule base, avec separation par dossiers.

### Server-side rendering prioritaire

Le HTML est principalement genere cote serveur.

Le JavaScript sert ensuite a:

- enrichir l'UX
- appeler les endpoints AJAX
- gerer filtres, notifications, chat et interactions admin

### Separation par role

Plutot qu'une seule interface universelle, le produit segmente fortement l'experience:

- client
- candidat
- prestataire
- dual
- admin

### Progressive enhancement

On observe une approche pragmatique:

- pages HTML utilisables
- puis enrichissement JS
- puis endpoints API pour fluidifier les ecrans

### Scripts de maintenance au lieu d'un vrai systeme de migrations

Le projet s'appuie sur des scripts PHP dedies pour:

- creer des tables
- ajouter des colonnes
- corriger des abonnements
- peupler des donnees

Cela montre une volonte d'industrialiser, mais pas encore un pipeline de migration unique et versionne.

### Defensive coding / fallback

Le module IA et le module abonnements montrent une logique de fallback importante:

- plusieurs strategies d'extraction texte
- repli sur donnees BDD
- traitement tolerant aux schemas variables

## Technologies, langages et outils

### Langages

- PHP
- HTML
- CSS
- JavaScript
- SQL
- Batch Windows pour certains scripts

### Backend

- PHP 8.x
- PDO
- MySQL / MariaDB
- cURL
- DOMDocument / DOMXPath

### Frontend

- Bootstrap 5
- Bootstrap Icons
- JavaScript vanilla
- AOS pour les animations
- CSS maison dans `assets/css`

### Dependances Composer observees

Depuis `composer.json` et `composer.lock`:

- `stripe/stripe-php`
- `spatie/pdf-to-text`
- `symfony/process`

### Services externes ou integrations

- Stripe
- API Mistral
- email via `mail()`
- outils systeme possibles pour PDF/OCR selon l'environnement

### Environnement de dev suppose

Le depot est clairement pense pour une execution locale type WAMP/LAMP avec une URL de base proche de:

- `http://localhost/lulu`

## Base de donnees: ce que le code laisse comprendre

Le projet depend fortement d'une base relationnelle riche.

Tables importantes detectees dans le code:

- `utilisateurs`
- `localisations`
- `categories_services`
- `profils_prestataires`
- `cvs`
- `prestataire_categories`
- `cv_categories`
- `messages`
- `notifications`
- `favoris`
- `logs_activite`
- `avis_notes`
- `portfolios`
- `candidatures`
- `abonnements`
- `plans_abonnement`
- `pricings`
- `subscription_requests`
- `demandes_upgrade`
- `paiements`
- `paiements_stripe`
- `messages_contact`
- `pages_statiques`

Important:

- aucun dump SQL complet n'est fourni dans le depot
- il existe plusieurs scripts de creation/correction partiels
- la base doit donc etre reconstruite ou stabilisee a partir du code et des scripts existants

## Installation et demarrage local

### Prerequis

- PHP 8.0+
- MySQL ou MariaDB
- Apache ou equivalent
- extensions PHP usuelles, notamment `curl`, `json`, `mbstring`
- Composer

### Etapes minimales

1. Placer le projet dans un vhost ou un dossier web, par exemple `c:\wamp64\www\lulu`
2. Configurer la base dans `config/db.php`
3. Verifier `APP_URL` et `BASE_URL` dans `config/config.php`
4. Installer les dependances Composer si besoin
5. Creer/adapter les tables attendues
6. Verifier l'existence et les droits des dossiers:
   - `uploads/`
   - `logs/`
7. Configurer l'IA et Stripe si ces modules doivent etre utilises

### Scripts utiles

Scripts remarquables detectes:

- `scripts/create-abonnements-table.php`
- `scripts/migrate-stripe.php`
- `scripts/fix-subscriptions.php`
- `scripts/cron-subscriptions.php`
- `scripts/fill-test-data.php`
- `scripts/populate-world-cities.php`
- `api/add-pricing-id-column.php`

Conseil: ces scripts doivent etre relus avant execution en production, car ils ne constituent pas un systeme de migrations transactionnelles complet.

## Etat actuel du projet

### Ce qui parait deja exploitable

- page d'accueil et pages publiques
- recherche publique de profils
- detail profil public
- dashboards par role
- gestion basique des favoris
- messagerie interne
- back-office admin deja dense
- exports CSV
- module candidatures + IA relativement avance

### Ce qui semble en transition

- le routage global
- les parcours d'authentification
- les flux d'abonnement
- la cohabitation entre pages legacy et nouvelles vues
- les layouts partages

### Mon evaluation synthese

Le projet est riche fonctionnellement et deja tres avance en surface produit, mais il souffre encore d'une fragmentation technique qui freine sa lisibilite et sa maintenabilite.

## Limites, anomalies et dette technique verifiees

Les points ci-dessous sont fondes sur la lecture directe du code.

### 1. Secrets et configuration sensible dans le code

Le fichier `config/config.php` contient encore une cle IA en dur.

Implication:

- risque de fuite de secret
- absence de separation nette entre code et configuration

### 2. CSRF affaibli dans le flux procedural d'auth

Dans `auth-handler.php`, un token CSRF invalide est loggue mais le bloc d'interruption est commente en mode debug.

Implication:

- le durcissement securite n'est pas complet sur ce flux

### 3. Systeme Stripe incomplet ou incoherent

Le code Stripe vise un workflow moderne, mais plusieurs incoherences sont visibles:

- `config/stripe.php` contient encore des placeholders
- `StripeGateway.php` attend des constantes qui ne sont pas definies dans `config/stripe.php`
- le gateway utilise aussi une cle `stripe_price_id` alors que la config active expose `price_id`

Implication:

- le flux Stripe n'est pas garanti "plug and play" sans ajustements

### 4. Routeur central non finalise

`core/Router.php` declare des routes vers:

- `AuthController@showLogin`
- `AuthController@showRegister`
- `AdminUserController@index`

or:

- `showLogin` et `showRegister` n'existent pas dans `AuthController`
- `controllers/AdminUserController.php` n'existe pas

Implication:

- le routeur central est present, mais pas totalement aligné avec le code reel

### 5. Layout partage incomplet

`views/layouts/main.php` reference:

- `views/components/navbar.php`
- `views/components/footer.php`

Ces fichiers n'existent pas dans `views/components/`.

Implication:

- certaines voies de rendu via `renderLayout()` ne sont pas completement fiabilisees

### 6. Recherche controllerisee et vue legacy pas totalement alignees

Dans `views/pages/search_results.php`, le filtre categorie utilise `name="category"` et `filters['category']`, alors que `SearchController` manipule surtout `categories[]`.

Implication:

- incoherence probable entre la vue controllerisee et la logique actuelle de recherche multiple

### 7. Multiplication de flux d'abonnement

Exemples constates:

- `views/candidat/abonnement.php` redirige immediatement vers `views/payments.php`, puis contient encore du vieux code
- `views/prestataire/subscription/checkout.php` contient un paiement simule cote front
- `SubscriptionController` gere encore des preuves de paiement manuelles
- `PaymentController` et `StripeGateway` gerent en parallele Stripe

Implication:

- le domaine "abonnement/paiement" est le principal chantier de consolidation

### 8. Lien mot de passe oublie manquant

`login.php` pointe vers `reset-password.php`, mais ce fichier n'existe pas a la racine.

Implication:

- rupture UX dans le parcours d'authentification

### 9. Versioning non stabilise

Le code annonce `APP_VERSION = 1.0.0` dans `config/config.php`, alors que l'ancien README affichait `2.0.0`.

Implication:

- la version fonctionnelle du produit n'est pas clairement normalisee

### 10. Absence de tests automatiques du projet

Je n'ai pas trouve:

- dossier `tests/`
- `phpunit.xml`
- suite de tests applicative

Implication:

- la non regression repose principalement sur des verifications manuelles

### 11. Base de donnees non pleinement gouvernee

Le depot contient des scripts de creation/correction, mais pas de schema unique de reference.

Implication:

- onboarding plus difficile
- risque d'ecarts entre environnements
- dette de migration accumulee

## Verification effectuee pour ce README

Ce README a ete produit apres:

- lecture de l'arborescence du projet
- inspection des pages racine
- lecture des controleurs principaux
- lecture des modeles principaux
- inspection des vues publiques, client, candidat, prestataire, dual et admin
- lecture des endpoints API majeurs
- lecture des scripts techniques et des fichiers de configuration

Verification complementaire realisee:

- controle syntaxique `php -l` par blocs sur les fichiers racine, `config`, `controllers`, `core`, `includes`, `models`, `api`, `views/pages`, `views/admin`, `views/client`, `views/candidat`, `views/prestataire`, `views/prestataire_candidat`, `views/auth`
- resultat: pas d'erreur de syntaxe detectee sur les blocs verifies

Limites de la verification:

- pas d'execution navigateur end-to-end
- pas de tests automatiques applicatifs disponibles
- pas de schema SQL unique livre dans le depot

## Forces du projet

- perimetre fonctionnel tres large
- segmentation metier claire par type d'utilisateur
- module IA candidat ambitieux
- back-office deja riche
- presence de scripts d'exploitation
- orientation produit bien identifiable

## Faiblesses structurelles actuelles

- architecture hybride parfois difficile a suivre
- duplication de flux et de pages
- dette technique sur auth / routing / paiements
- configuration sensible encore dans le code
- absence de socle de tests et de migrations robustes

## Priorites de consolidation recommandees

Si l'objectif est de rendre le projet plus stable et plus maintenable, les priorites les plus rentables sont:

1. unifier le routage autour d'un seul mode de fonctionnement
2. choisir un seul vrai systeme d'abonnement/paiement
3. sortir tous les secrets et URLs sensibles vers des variables d'environnement
4. etablir un schema SQL officiel et versionne
5. remettre a plat l'authentification et le CSRF procedural
6. fiabiliser les layouts partages
7. ajouter des tests minimums sur les flux critiques

## Licence

Le depot contient une licence proprietaire dans `LICENSE`.

En l'etat du fichier, le code est consultable pour etude/evaluation, mais non librement reutilisable ou redistribuable sans autorisation explicite.

## Conclusion

LULU-OPEN est un projet reel, ambitieux et deja riche en fonctionnalites. Il ne s'agit pas d'une simple vitrine, mais d'une plateforme complete avec:

- moteur de recherche
- profils multi-role
- messagerie
- administration
- paiements
- automatisation
- assistance IA

Sa principale limite aujourd'hui n'est pas l'absence d'idees ou de fonctionnalites, mais la coexistence de plusieurs generations de code. Une phase de consolidation architecturale aurait beaucoup de valeur, car la base fonctionnelle est deja importante.
