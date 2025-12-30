# Scripts de Test - LULU-OPEN

## 🔧 Script de Remplissage de la Base de Données

### Utilisation

**Option 1 : Via l'interface web (Recommandé)**
1. Accédez à : `http://localhost/lulu/scripts/run-fill-test.php`
2. Cliquez sur le bouton "Exécuter le script"
3. Attendez la fin de l'exécution

**Option 2 : Via la ligne de commande**
```bash
cd c:\wamp64\www\lulu\scripts
php fill-test-data.php
```

### Ce que le script crée

- **5 utilisateurs de test** (prestataires et candidats)
  - Email : `jean.dupont@test.com`
  - Email : `marie.martin@test.com`
  - Email : `pierre.bernard@test.com`
  - Email : `sophie.dubois@test.com`
  - Email : `luc.moreau@test.com`
  - Mot de passe pour tous : `Test123!`

- **5 abonnements** (avec différents statuts : actif, suspendu, expiré)
- **Plusieurs paiements** (validés, en attente, échoués)
- **3 demandes d'activation** (en attente, en cours, approuvées, refusées)
- **Notifications** pour chaque utilisateur

### Prérequis

- Les tables de la base de données doivent exister
- Au moins 1 plan d'abonnement doit être créé dans `plans_abonnement`

### Après l'exécution

Vous pouvez tester :
- ✅ Onglet Utilisateurs
- ✅ Onglet Validations
- ✅ Onglet Abonnements
- ✅ Onglet Paiements
- ✅ Export CSV
- ✅ Toutes les actions CRUD

### Nettoyage

Pour supprimer les données de test :
```sql
-- Supprimer les paiements de test
DELETE FROM paiements WHERE transaction_id LIKE 'TXN_%';

-- Supprimer les abonnements de test
DELETE FROM abonnements WHERE utilisateur_id IN (
    SELECT id FROM utilisateurs WHERE email LIKE '%@test.com'
);

-- Supprimer les utilisateurs de test
DELETE FROM utilisateurs WHERE email LIKE '%@test.com';
```

## 📊 Export CSV

Les exports CSV sont disponibles sur :
- **Paiements** : `/lulu/api/admin-payments-export.php`
- **Abonnements** : `/lulu/api/admin-subscriptions-export.php`

Les filtres de la page sont automatiquement appliqués à l'export.

## 🐛 Dépannage

**Erreur "Aucun plan trouvé"**
- Créez d'abord des plans d'abonnement via l'interface admin ou SQL

**Erreur de connexion à la base**
- Vérifiez que WAMP est démarré
- Vérifiez les identifiants dans `config/db.php`

**Erreur 404 sur l'export CSV**
- Vérifiez que le fichier existe dans `/api/`
- Vérifiez les permissions du dossier
