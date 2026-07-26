# Audit systématique — Lulu-open v2

_Basé sur le code de la branche `v2`, vérifié par exécution locale (serveur PHP intégré + MySQL 8) le 26/07/2026._

## Verdict global

Le refactor v2 est **de bonne qualité** : MVC propre, couche de vues **entièrement câblée** (0 vue orpheline réelle), layouts cohérents (aucune page ne réinvente son `<html>`), sécurité de base présente (CSRF, sessions durcies, en-têtes). L'app **boote et tous les parcours majeurs répondent 200** après 4 correctifs.

Les problèmes restants sont surtout : (1) une **ambiguïté du modèle métier** dans les gardes de rôle, (2) la **fonctionnalité IA non branchée**, (3) **deux styles d'API incompatibles** avec le déploiement Render, (4) du **code mort / dupliqué** résiduel.

---

## 🔴 Bloquants (à traiter avant prod)

### B1. Modèle métier : mauvaises gardes de rôle sur les candidatures
Modèle cible : **entreprise** = recruteur (publie, reçoit, source) ; **client** = candidat/prestataire (postule, propose des services, est contacté).
Or dans `routes.php`, tout ce qui relève du fait de **postuler** est protégé par `role:entreprise` :

| Ligne | Route | Actuel | Devrait être |
|---|---|---|---|
| 44 | `/offres/{id}/postuler` | `role:entreprise` | `role:client` |
| 49 | `POST /applications` | `role:entreprise` | `role:client` |
| 50 | `/entreprise/candidatures` (envoyées) | `role:entreprise` | `role:client` |
| 53 | `POST /applications/{id}/delete` | `role:entreprise` | `role:client` |
| 51 | `/entreprise/candidatures/recues` | `role:entreprise` | ✅ correct (reçues) |
| 52 | `POST .../{id}/status` | `role:entreprise` | ✅ correct |

➡️ Conséquence actuelle : **un client ne peut pas postuler** ; c'est l'entreprise qui postulerait à ses propres offres. Les chemins d'URL `/entreprise/candidatures*` devraient aussi migrer côté `client` pour la cohérence.

### B2. Deux styles d'API incompatibles avec Render
- **API routées** (OK partout) : `saved-searches`, `stripe-webhook`, `admin-export` → passent par `routes.php`.
- **API en accès direct** (OK sous Apache/WAMP, **KO sous `router.php`/Render**) : `api/messages.php`, `api/notifications.php` sont appelées en `fetch('api/xxx.php')` par le JS. Le serveur PHP intégré via `router.php` ne les exécute pas → **404 en prod**.

➡️ À uniformiser : donner une vraie route à chaque endpoint (recommandé), **ou** autoriser `router.php` à exécuter les `.php` de `api/`.

### B3. Fonctionnalité IA non branchée
5 endpoints existent mais **ne sont référencés nulle part** (ni route, ni fetch JS) :
`api/ai-analyze-cv.php`, `ai-cover-letter.php`, `ai-optimize-cv.php`, `ai-profile-score.php`, `ai-write-offer.php`.
Les classes IA associées (`includes/ai/CvAnalyzer`, `CvOptimizer`, `CoverLetterGenerator`, `OfferWriter`, `ProfileEnhancer`, `MatchingEngine`) existent aussi. ➡️ **Le différenciateur IA est du back-end sans front.** + clé `MISTRAL_API_KEY` vide dans `.env`.

---

## 🟠 Importants

### I1. Paiement Stripe non configuré
`.env` a toutes les clés Stripe **vides** (`STRIPE_SECRET_KEY`, `STRIPE_PRICE_*`). Le flux abonnement/checkout ne fonctionnera pas tant qu'un compte Stripe n'est pas branché. À décider : Stripe réel dès le lancement, ou abonnements gratuits/manuels au départ.

### I2. Code mort — méthodes de contrôleurs jamais appelées
| Méthode | Fichier | Remplacée par |
|---|---|---|
| `MessageController::sendMessage` | duplique `api/messages.php` | logique inline dans l'API |
| `MessageController::deleteMessage` | idem | idem |
| `MessageController::deleteConversation` | idem | idem |
| `AdminController::exportUsers` | — | `api/admin-export.php` |
| `PageController::pricing` | — | `SubscriptionController::showPlans` |

### I3. Doublons
- **Messagerie dupliquée** : la logique d'envoi/suppression existe à la fois dans `MessageController` (mort) et dans `api/messages.php` (utilisé). Une seule source de vérité à garder.
- `api/favorites.php` (orphelin) **duplique** `FavoriteController::toggle` (routé). Candidat à suppression.

---

## 🟡 Mineurs / propreté

- **Assets morts** : `assets/css/hero-premium.css` et `assets/js/hero-premium.js` ne sont **référencés nulle part**.
- **README obsolète** : décrit le v1 disparu (rôles prestataire/candidat/dual). À réécrire pour le v2.
- **Dépendance CDN** : Bootstrap chargé depuis `cdn.jsdelivr.net` (OK, mais dépendance réseau externe ; la CSP l'autorise déjà).
- **Pas de `.htaccess`** : l'app ne tourne que via `router.php` (compatible Render, mais pas de fallback Apache).

---

## ✅ Déjà corrigé cette session (commit `eafc5ec`)
1. Runner de migrations (transactions retirées — DDL MySQL = commit implicite).
2. Migration 003 (`ADD COLUMN IF NOT EXISTS` → syntaxe MySQL 8).
3. Login/CSRF (alignement `APP_URL` ↔ path du cookie de session).
4. `/messages` 500 (`ATTR_EMULATE_PREPARES=true` pour la réutilisation des placeholders nommés).

---

## Points forts confirmés
- Couche de vues 100 % câblée (aucun template rendu manquant, aucune vue réellement orpheline).
- Layouts cohérents, séparation front/back nette.
- Migrations versionnées + runner ; seed idempotent en place.
- Sécurité de base : CSRF, cookies `httponly`/`samesite`, en-têtes de sécurité, hash de mot de passe standard.
