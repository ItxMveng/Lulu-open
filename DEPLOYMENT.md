# Déploiement de LULU-OPEN sur Render (gratuit)

Stack : PHP 8.2 + Apache (Docker), MySQL managé externe. Le déploiement est piloté par
`Dockerfile`, `docker/entrypoint.sh`, `.htaccess` et `render.yaml`.

## 1. Pré-requis
- Le code poussé sur GitHub (`git push`).
- Un compte [Render](https://render.com) (gratuit).
- Une base **MySQL** managée gratuite. Render n'offre pas MySQL en gratuit → utiliser
  un fournisseur externe, par ex. **Clever Cloud** (add-on MySQL gratuit « DEV »),
  **Aiven**, ou **freedb.tech**. Récupérer : hôte, port, nom de base, utilisateur, mot de passe.

## 2. Créer la base MySQL (exemple Clever Cloud)
1. Créer un add-on **MySQL** (plan gratuit).
2. Noter les identifiants : `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`.
   (Les migrations créeront les tables automatiquement au premier démarrage.)

## 3. Créer le service web sur Render
1. **New → Blueprint** et sélectionner le dépôt : Render lit `render.yaml`.
   (ou **New → Web Service**, runtime **Docker**, en pointant sur le repo.)
2. Renseigner les variables marquées `sync: false` :
   - `APP_URL` : l'URL du service, ex. `https://lulu-open.onrender.com` (à remettre après le 1er déploiement une fois l'URL connue).
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` (de l'étape 2).
   - `MISTRAL_API_KEY` : votre clé Mistral (⚠️ générer une **nouvelle** clé, l'ancienne a été exposée).
   - `MAIL_HOST`, `MAIL_USER`, `MAIL_PASS`, `MAIL_FROM_ADDRESS` : un SMTP réel
     (Brevo/Sendinblue, Mailgun, ou SMTP Gmail avec mot de passe d'application).
   - `APP_KEY` est généré automatiquement par Render.
3. Déployer. Au démarrage, l'entrypoint :
   - configure Apache sur le port fourni par Render,
   - exécute les **migrations** (idempotentes),
   - lance Apache.

## 4. Après le premier déploiement
- Mettre `APP_URL` à l'URL réelle (`https://<nom>.onrender.com`) puis redéployer.
- (Optionnel) **Peupler des données de démo** : Render → onglet **Shell** du service →
  `php scripts/seed.php` (⚠️ supprime et recrée les comptes talents/entreprises).
  En production réelle, ne pas lancer le seed : laisser les vrais comptes se créer.

## 5. Points d'attention
- **Fichiers uploadés éphémères** : le disque Render se réinitialise à chaque déploiement.
  Les CV/photos uploadés seront perdus au redéploiement. Pour la persistance, prévoir un
  stockage objet (S3 / Cloudflare R2) — amélioration post-déploiement.
- **Plan gratuit** : le service se met en veille après inactivité (premier accès plus lent).
- **Lecture des PDF** : `pdftotext` (poppler) est inclus dans l'image.
- **IA** : sans `MISTRAL_API_KEY`, l'IA fonctionne en mode local dégradé.
- **HTTPS** : géré par Render ; l'app détecte `X-Forwarded-Proto` (cookies sécurisés OK).

## 6. Mise à jour
Chaque `git push` sur la branche suivie déclenche un redéploiement automatique.
