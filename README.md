# 🚀 LULU-OPEN - Marketplace des Talents

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/ItxMveng/Lulu-open)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://php.net/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3.svg)](https://getbootstrap.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

> **LULU-OPEN** est une plateforme innovante qui révolutionne la mise en relation entre prestataires de services, candidats à l'emploi et recruteurs. Une marketplace complète pour connecter les talents aux opportunités.

## 📋 Table des matières

- [🎯 Présentation](#-présentation)
- [✨ Fonctionnalités](#-fonctionnalités)
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

### 🎯 Objectifs

- Simplifier la recherche de talents qualifiés
- Faciliter la mise en relation professionnelle
- Offrir une plateforme sécurisée et intuitive
- Démocratiser l'accès aux opportunités professionnelles

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

### 💬 **Communication**
- Messagerie intégrée en temps réel
- Notifications push et email
- Historique des conversations
- Interface de chat moderne et responsive

### 🔐 **Sécurité & Authentification**
- Système d'authentification robuste
- Vérification des profils
- Protection CSRF et validation des données
- Gestion des sessions sécurisée

### 💳 **Système d'Abonnement**
- Plans d'abonnement flexibles (mensuel, trimestriel, annuel)
- Gestion des paiements et facturation
- Tableau de bord administrateur complet
- Statistiques et analytics avancées

### 🤖 **Intelligence Artificielle**
- Extraction automatique de données CV (Mistral AI)
- Suggestions de profils personnalisées
- Analyse de compatibilité emploi/candidat
- Optimisation des recherches

## 🏗️ Architecture

```
lulu/
├── 📁 api/                    # API endpoints
├── 📁 assets/                 # Ressources statiques
│   ├── css/                   # Feuilles de style
│   ├── js/                    # Scripts JavaScript
│   └── images/                # Images et médias
├── 📁 config/                 # Configuration
│   ├── config.php             # Configuration générale
│   └── db.php                 # Configuration base de données
├── 📁 controllers/            # Contrôleurs MVC
├── 📁 core/                   # Noyau de l'application
├── 📁 includes/               # Fichiers d'inclusion
│   ├── ai/                    # Modules IA
│   └── middleware/            # Middlewares
├── 📁 models/                 # Modèles de données
├── 📁 views/                  # Vues et templates
│   ├── admin/                 # Interface administrateur
│   ├── client/                # Interface client
│   ├── candidat/              # Interface candidat
│   └── prestataire/           # Interface prestataire
├── 📁 uploads/                # Fichiers uploadés
├── 📁 vendor/                 # Dépendances Composer
└── 📁 scripts/                # Scripts utilitaires
```

### 🎨 **Pattern MVC**
- **Modèles** : Gestion des données et logique métier
- **Vues** : Interface utilisateur et templates
- **Contrôleurs** : Logique de traitement des requêtes

## 🛠️ Technologies

### **Backend**
- ![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php) **PHP 8.0+** - Langage principal
- ![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql) **MySQL 8.0+** - Base de données
- **Composer** - Gestionnaire de dépendances

### **Frontend**
- ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap) **Bootstrap 5.3** - Framework CSS
- ![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript) **JavaScript ES6+** - Interactivité
- **AOS** - Animations on scroll
- **Bootstrap Icons** - Icônes

### **Intelligence Artificielle**
- **Mistral AI** - Traitement du langage naturel
- **Spatie PDF-to-Text** - Extraction de texte PDF

### **Outils & Services**
- **Git** - Contrôle de version
- **WAMP/XAMPP** - Environnement de développement
- **Composer** - Gestion des dépendances PHP

## 📦 Installation

### Prérequis

- PHP 8.0 ou supérieur
- MySQL 8.0 ou supérieur
- Composer
- Serveur web (Apache/Nginx)

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

5. **Importer la structure de base**
```bash
mysql -u username -p lulu_open < database/structure.sql
```

6. **Configurer les permissions**
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

### 🗄️ Base de données

La structure de base de données comprend :
- **utilisateurs** - Gestion des comptes utilisateurs
- **profils_prestataires** - Profils des prestataires
- **cvs** - CVs des candidats
- **categories** - Catégories de services/emplois
- **messages** - Système de messagerie
- **abonnements** - Gestion des abonnements
- **favoris** - Système de favoris

## 🚀 Utilisation

### 👤 **Pour les Prestataires**
1. Créer un compte prestataire
2. Compléter son profil professionnel
3. Ajouter ses services et tarifs
4. Recevoir et répondre aux demandes

### 💼 **Pour les Candidats**
1. Créer un compte candidat
2. Uploader son CV (extraction automatique)
3. Compléter ses informations
4. Être découvert par les recruteurs

### 🏢 **Pour les Clients/Recruteurs**
1. Créer un compte client
2. Rechercher des profils
3. Contacter les professionnels
4. Gérer ses favoris et demandes

### 👨‍💼 **Interface Administrateur**
- Gestion des utilisateurs et profils
- Modération des contenus
- Statistiques et analytics
- Gestion des abonnements et paiements

## 📱 Captures d'écran

### 🏠 Page d'accueil
Interface moderne avec recherche avancée et présentation des catégories.

### 🔍 Résultats de recherche
Affichage optimisé des profils avec filtres et tri personnalisables.

### 💬 Messagerie
Interface de chat en temps réel pour faciliter les échanges.

### 📊 Tableau de bord
Dashboards personnalisés selon le type d'utilisateur.

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

## 🐛 Signaler un bug

Utilisez les [GitHub Issues](https://github.com/ItxMveng/Lulu-open/issues) pour signaler des bugs ou proposer des améliorations.

## 📈 Roadmap

### Version 1.1 (À venir)
- [ ] API REST complète
- [ ] Application mobile (React Native)
- [ ] Système de géolocalisation avancé
- [ ] Intégration paiements en ligne

### Version 1.2 (Futur)
- [ ] Système de recommandations IA
- [ ] Chat vidéo intégré
- [ ] Marketplace de formations
- [ ] Analytics avancées

## 📞 Support

- **Email** : support@lulu-open.com
- **Documentation** : [Wiki du projet](https://github.com/ItxMveng/Lulu-open/wiki)
- **Issues** : [GitHub Issues](https://github.com/ItxMveng/Lulu-open/issues)

## 👨‍💻 Auteur

**ItxMveng**
- GitHub: [@ItxMveng](https://github.com/ItxMveng)
- Projet: [LULU-OPEN](https://github.com/ItxMveng/Lulu-open)

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

<div align="center">

**⭐ N'hésitez pas à donner une étoile si ce projet vous plaît ! ⭐**

Made with ❤️ by [ItxMveng](https://github.com/ItxMveng)

</div>