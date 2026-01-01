# 🚀 LULU-OPEN - Marketplace des Talents

[![Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](https://github.com/ItxMveng/Lulu-open)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg)](https://getbootstrap.com/)
[![Stripe](https://img.shields.io/badge/Stripe-Payment-635BFF.svg)](https://stripe.com/)
[![License](https://img.shields.io/badge/license-Proprietary-red.svg)](LICENSE)

> **LULU-OPEN** est une plateforme innovante qui révolutionne la mise en relation entre prestataires de services, candidats à l'emploi et recruteurs. Une marketplace complète avec système de paiement automatisé Stripe et intelligence artificielle intégrée.

## 📋 Table des matières

- [🎯 Présentation](#-présentation)
- [✨ Fonctionnalités](#-fonctionnalités)
- [🆕 Nouveautés v2.0](#-nouveautés-v20)
- [🏗️ Architecture](#️-architecture)
- [🛠️ Technologies](#️-technologies)
- [📦 Installation](#-installation)
- [⚙️ Configuration](#️-configuration)
- [🚀 Utilisation](#-utilisation)
- [📱 Captures d'écran](#-captures-décran)
- [🤝 Contribution](#-contribution)
- [📄 Licence](#-licence)

## 🎯 Présentation

LULU-OPEN est bien plus qu'une simple marketplace. C'est un écosystème complet où :

- **Prestataires de services** peuvent présenter leurs compétences et trouver des clients
- **Candidats à l'emploi** peuvent publier leur CV et être découverts par les recruteurs
- **Clients et recruteurs** peuvent rechercher et contacter les profils qui correspondent à leurs besoins
- **Administrateurs** disposent d'outils avancés de gestion et d'analytics

### 🎯 Objectifs

- Simplifier la recherche de talents qualifiés
- Faciliter la mise en relation professionnelle
- Offrir une plateforme sécurisée et intuitive
- Démocratiser l'accès aux opportunités professionnelles
- Automatiser la gestion des abonnements et paiements

## ✨ Fonctionnalités

### 🔍 **Recherche Avancée**
- Moteur de recherche intelligent avec filtres multiples
- Recherche par catégorie, localisation et compétences
- Suggestions automatiques et géolocalisation
- Sauvegarde des recherches favorites

### 👥 **Gestion des Profils**
- Profils détaillés pour prestataires et candidats
- Upload et gestion de CV avec extraction automatique (IA)
- Portfolio et galerie de réalisations
- Système de notation et d'avis clients

### 💬 **Communication Moderne**
- Messagerie intégrée en temps réel
- Support des fichiers et emojis
- Suppression de messages
- Confirmations de lecture
- Notifications push et email
- Interface responsive et moderne

### 🔐 **Sécurité & Authentification**
- Système d'authentification robuste
- Vérification des profils
- Protection CSRF et validation des données
- Gestion des sessions sécurisée
- Middleware de protection des routes

### 💳 **Système de Paiement Stripe Intégré**
- Paiements automatisés via Stripe
- Plans d'abonnement flexibles (mensuel: 29.99€, trimestriel: 79.99€, annuel: 299€)
- Activation automatique des abonnements
- Gestion des webhooks Stripe
- Interface de paiement sécurisée
- Historique des transactions

### 🤖 **Intelligence Artificielle**
- Extraction automatique de données CV (Mistral AI)
- Suggestions de profils personnalisées
- Analyse de compatibilité emploi/candidat
- Optimisation des recherches
- Insights automatiques pour les administrateurs

## 🆕 Nouveautés v2.0

### 💰 **Système de Paiement Automatisé**
- **Intégration Stripe complète** : Paiements sécurisés et automatisés
- **Activation immédiate** : Les abonnements s'activent automatiquement après paiement
- **Gestion des webhooks** : Synchronisation en temps réel avec Stripe
- **Plans unifiés** : Tarification cohérente sur toute la plateforme

### 🎛️ **Interface Admin Modernisée**
- **Dashboard en temps réel** : Statistiques live avec données réelles
- **Gestion des abonnements** : Vue d'ensemble de tous les utilisateurs
- **Monitoring Stripe** : Suivi des paiements et abonnements
- **Analytics avancées** : Insights IA et métriques de performance
- **CRUD complet** : Gestion des catégories avec fonctionnalités complètes

### 💬 **Système de Messagerie Unifié**
- **Interface moderne** : Design cohérent pour tous les types d'utilisateurs
- **Fonctionnalités avancées** : Upload de fichiers, emojis, suppression
- **Temps réel** : Mise à jour instantanée des conversations
- **Multi-plateforme** : Même expérience pour admin, prestataires, candidats, clients

### 📊 **Gestion des Données**
- **Migration automatique** : Scripts de mise à jour de la base de données
- **Synchronisation** : Cohérence entre anciens et nouveaux systèmes
- **Sauvegarde** : Protection des données existantes
- **Performance** : Optimisation des requêtes et indexation

## 🏗️ Architecture

```
lulu/
├── 📁 api/                    # API endpoints
│   ├── admin-categories.php   # CRUD catégories
│   ├── admin-messages.php     # Messagerie admin
│   ├── admin-subscription-actions.php # Actions abonnements
│   ├── admin-users.php        # Gestion utilisateurs
│   ├── messages.php           # API messagerie unifiée
│   └── stripe-webhook.php     # Webhooks Stripe
├── 📁 assets/                 # Ressources statiques
│   ├── css/                   # Feuilles de style
│   ├── js/                    # Scripts JavaScript
│   └── images/                # Images et médias
├── 📁 config/                 # Configuration
│   ├── config.php             # Configuration générale
│   ├── db.php                 # Configuration base de données
│   └── stripe.php             # Configuration Stripe
├── 📁 controllers/            # Contrôleurs MVC
│   └── PaymentController.php  # Contrôleur paiements
├── 📁 core/                   # Noyau de l'application
├── 📁 includes/               # Fichiers d'inclusion
│   ├── ai/                    # Modules IA
│   ├── middleware/            # Middlewares
│   └── StripeGateway.php      # Passerelle Stripe
├── 📁 models/                 # Modèles de données
│   ├── Admin.php              # Modèle admin avec analytics
│   └── Message.php            # Modèle messagerie
├── 📁 views/                  # Vues et templates
│   ├── admin/                 # Interface administrateur
│   │   ├── categories.php     # Gestion catégories CRUD
│   │   ├── dashboard.php      # Dashboard temps réel
│   │   ├── messages.php       # Messagerie moderne
│   │   ├── payments.php       # Historique paiements
│   │   ├── plans.php          # Gestion plans Stripe
│   │   ├── statistics.php     # Analytics avancées
│   │   ├── stripe-dashboard.php # Monitoring Stripe
│   │   └── subscriptions.php  # Gestion abonnements
│   ├── client/                # Interface client
│   ├── candidat/              # Interface candidat
│   │   └── settings.php       # Paramètres utilisateur
│   └── prestataire/           # Interface prestataire
├── 📁 scripts/                # Scripts utilitaires
│   ├── cron-subscriptions.php # Tâche automatisée
│   ├── fix-subscriptions.php  # Correction abonnements
│   └── migrate-stripe.php     # Migration Stripe
├── 📁 uploads/                # Fichiers uploadés
└── 📁 vendor/                 # Dépendances Composer
```

### 🎨 **Pattern MVC Étendu**
- **Modèles** : Gestion des données avec analytics intégrées
- **Vues** : Interface utilisateur responsive et moderne
- **Contrôleurs** : Logique métier avec intégration Stripe
- **API** : Endpoints RESTful pour toutes les fonctionnalités

## 🛠️ Technologies

### **Backend**
- ![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php) **PHP 8.0+** - Langage principal
- ![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql) **MySQL 8.0+** - Base de données
- **Composer** - Gestionnaire de dépendances
- **PDO** - Couche d'abstraction base de données

### **Frontend**
- ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap) **Bootstrap 5.3** - Framework CSS
- ![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript) **JavaScript ES6+** - Interactivité
- **AOS** - Animations on scroll
- **Bootstrap Icons** - Icônes
- **Chart.js** - Graphiques et analytics

### **Paiements & Intégrations**
- ![Stripe](https://img.shields.io/badge/Stripe-API-635BFF?style=flat&logo=stripe) **Stripe API** - Paiements sécurisés
- **Webhooks** - Synchronisation temps réel
- **Sessions** - Gestion des paiements

### **Intelligence Artificielle**
- **Mistral AI** - Traitement du langage naturel
- **Spatie PDF-to-Text** - Extraction de texte PDF
- **Analytics IA** - Insights automatiques

### **Outils & Services**
- **Git** - Contrôle de version
- **WAMP/XAMPP** - Environnement de développement
- **Composer** - Gestion des dépendances PHP
- **Cron Jobs** - Tâches automatisées

## 📦 Installation

### Prérequis

- PHP 8.0 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Serveur web (Apache/Nginx)
- Compte Stripe (pour les paiements)

### 🚀 Installation rapide

1. **Cloner le repository**
```bash
git clone https://github.com/ItxMveng/Lulu-open.git
cd Lulu-open
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configuration de la base de données**
```sql
CREATE DATABASE lulu_open;
```

4. **Configurer l'environnement**
```php
// config/db.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lulu_open');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

5. **Configuration Stripe**
```php
// config/stripe.php
define('STRIPE_SECRET_KEY', 'sk_test_...');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');
```

6. **Importer la structure de base**
```bash
mysql -u username -p lulu_open < database/structure.sql
```

7. **Exécuter les migrations**
```bash
php scripts/migrate-stripe.php
php scripts/fix-subscriptions.php
```

8. **Configurer les permissions**
```bash
chmod 755 uploads/
chmod 755 logs/
```

## ⚙️ Configuration

### 🔧 Configuration principale

Éditez le fichier `config/config.php` :

```php
// Configuration générale
define('APP_NAME', 'LULU-OPEN');
define('APP_URL', 'http://localhost/lulu');
define('APP_ENV', 'development'); // 'production' pour la prod

// Configuration IA Mistral
define('AI_API_KEY', 'votre_clé_api_mistral');
define('AI_MODEL_NAME', 'mistral-large-latest');

// Configuration email
define('SMTP_HOST', 'votre_smtp_host');
define('SMTP_USERNAME', 'votre_email');
define('SMTP_PASSWORD', 'votre_mot_de_passe');
```

### 💳 Configuration Stripe

Éditez le fichier `config/stripe.php` :

```php
// Clés API Stripe
define('STRIPE_SECRET_KEY', 'sk_test_...');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_...');

// IDs des produits Stripe
define('STRIPE_MONTHLY_PRICE_ID', 'price_...');
define('STRIPE_QUARTERLY_PRICE_ID', 'price_...');
define('STRIPE_YEARLY_PRICE_ID', 'price_...');
```

### 🗄️ Base de données

La structure de base de données comprend :
- **utilisateurs** - Gestion des comptes avec abonnements
- **profils_prestataires** - Profils des prestataires
- **cvs** - CVs des candidats
- **categories_services** - Catégories de services/emplois
- **messages** - Système de messagerie unifié
- **paiements_stripe** - Historique des paiements Stripe
- **demandes_upgrade** - Demandes d'upgrade d'abonnement
- **notifications** - Système de notifications

## 🚀 Utilisation

### 👤 **Pour les Prestataires**
1. Créer un compte prestataire
2. Compléter son profil professionnel
3. Choisir un plan d'abonnement
4. Effectuer le paiement via Stripe
5. Ajouter ses services et tarifs
6. Recevoir et répondre aux demandes

### 💼 **Pour les Candidats**
1. Créer un compte candidat
2. Uploader son CV (extraction automatique IA)
3. Souscrire à un abonnement premium
4. Compléter ses informations
5. Être découvert par les recruteurs

### 🏢 **Pour les Clients/Recruteurs**
1. Créer un compte client
2. Rechercher des profils
3. Contacter les professionnels
4. Gérer ses favoris et demandes

### 👨💼 **Interface Administrateur**
- **Dashboard temps réel** : Statistiques live et KPIs
- **Gestion Stripe** : Monitoring des paiements et abonnements
- **Gestion des utilisateurs** : CRUD complet avec détails
- **Analytics avancées** : Insights IA et métriques
- **Messagerie centralisée** : Communication avec tous les utilisateurs
- **Gestion des catégories** : CRUD avec comptage d'utilisation

## 📱 Captures d'écran

### 🏠 Page d'accueil
Interface moderne avec recherche avancée et présentation des catégories.

### 💳 Interface de paiement Stripe
Processus de paiement sécurisé avec activation automatique.

### 📊 Dashboard Admin
Tableau de bord avec statistiques en temps réel et analytics.

### 💬 Messagerie moderne
Interface de chat unifiée avec support fichiers et emojis.

### 🔍 Résultats de recherche
Affichage optimisé des profils avec filtres et tri personnalisables.

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. **Fork** le projet
2. Créer une **branche** pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. **Commit** vos changements (`git commit -m 'Add some AmazingFeature'`)
4. **Push** vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une **Pull Request**

### 📋 Guidelines de contribution

- Respecter les standards de codage PHP PSR-12
- Documenter les nouvelles fonctionnalités
- Tester les modifications avant soumission
- Utiliser des messages de commit descriptifs
- Suivre l'architecture MVC existante

## 🐛 Signaler un bug

Utilisez les [GitHub Issues](https://github.com/ItxMveng/Lulu-open/issues) pour signaler des bugs ou proposer des améliorations.

## 📈 Roadmap

### Version 2.1 (À venir)
- [ ] API REST complète
- [ ] Application mobile (React Native)
- [ ] Système de géolocalisation avancé
- [ ] Notifications push en temps réel
- [ ] Intégration PayPal

### Version 2.2 (Futur)
- [ ] Système de recommandations IA avancé
- [ ] Chat vidéo intégré
- [ ] Marketplace de formations
- [ ] Analytics prédictives
- [ ] Multi-langues

## 📞 Support

- **Email** : support@lulu-open.com
- **Documentation** : [Wiki du projet](https://github.com/ItxMveng/Lulu-open/wiki)
- **Issues** : [GitHub Issues](https://github.com/ItxMveng/Lulu-open/issues)
- **Discussions** : [GitHub Discussions](https://github.com/ItxMveng/Lulu-open/discussions)

## 👨💻 Auteur

**ItxMveng**
- GitHub: [@ItxMveng](https://github.com/ItxMveng)
- Projet: [LULU-OPEN](https://github.com/ItxMveng/Lulu-open)
- Email: francisitoua05@gmail.com

## 📄 Licence

Ce projet est sous licence propriétaire. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

**Usage commercial :** Pour toute utilisation commerciale, contactez francisitoua05@gmail.com

## 🙏 Remerciements

- **Stripe** pour leur excellente API de paiement
- **Mistral AI** pour les capacités d'intelligence artificielle
- **Bootstrap** pour le framework CSS
- **La communauté open source** pour les outils et bibliothèques

---

<div align="center">

**⭐ N'hésitez pas à donner une étoile si ce projet vous plaît ! ⭐**

**🚀 LULU-OPEN v2.0 - Marketplace des Talents avec IA et Paiements Automatisés 🚀**

Made with ❤️ by [ItxMveng](https://github.com/ItxMveng)

</div>