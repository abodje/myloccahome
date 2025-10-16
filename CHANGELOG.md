# 📝 Changelog MYLOCCA - Système de tâches et devises

## 🆕 Version 2.0 - 11 Octobre 2025

### ✨ Nouvelles fonctionnalités majeures

#### 1. Système de tâches automatisées
- ✅ Création de l'entité `Task` avec gestion complète des tâches programmées
- ✅ Service `TaskManagerService` pour l'exécution et la gestion des tâches
- ✅ Repository `TaskRepository` avec méthodes de recherche avancées
- ✅ Interface d'administration complète pour gérer les tâches
- ✅ Commandes console pour l'exécution automatique

#### 2. Service de notifications par email
- ✅ Service `NotificationService` avec 4 types de notifications :
  - Quittances de loyer automatiques
  - Rappels de paiement
  - Alertes d'expiration de contrats
  - Test de configuration email
- ✅ Templates HTML professionnels pour tous les emails
- ✅ Support de la configuration SMTP complète
- ✅ Envoi manuel et automatique

#### 3. Système multi-devises amélioré
- ✅ Ajout du champ `decimalPlaces` dans l'entité `Currency`
- ✅ Concept de "devise active" appliquée automatiquement
- ✅ Extension Twig `CurrencyExtension` avec filtres et fonctions
- ✅ Interface d'administration pour gérer les devises
- ✅ Convertisseur de devises intégré

#### 4. Extensions Twig
- ✅ `CurrencyExtension` : Gestion de la devise dans les templates
- ✅ `SystemExtension` : Fonctions système (disk_free_space, memory, etc.)

### 📁 Fichiers créés

#### Entités
- `src/Entity/Task.php` - Gestion des tâches programmées

#### Services
- `src/Service/NotificationService.php` - Service de notifications
- `src/Service/TaskManagerService.php` - Gestionnaire de tâches
- `src/Service/CurrencyService.php` - Méthodes ajoutées pour devise active

#### Repositories
- `src/Repository/TaskRepository.php` - Requêtes pour les tâches

#### Commandes
- `src/Command/TaskRunnerCommand.php` - Exécution des tâches
- `src/Command/SendRentReceiptsCommand.php` - Envoi des quittances

#### Contrôleurs
- `src/Controller/Admin/TaskController.php` - Gestion des tâches
- `src/Controller/Admin/SettingsController.php` - Route ajoutée pour devise active

#### Formulaires
- `src/Form/TaskType.php` - Formulaire de création de tâches
- `src/Form/CurrencyType.php` - Champ `decimalPlaces` ajouté

#### Extensions Twig
- `src/Twig/CurrencyExtension.php` - Filtres de devise
- `src/Twig/SystemExtension.php` - Fonctions système

#### Templates - Emails
- `templates/emails/rent_receipt.html.twig` - Quittance de loyer
- `templates/emails/payment_reminder.html.twig` - Rappel de paiement
- `templates/emails/lease_expiration.html.twig` - Expiration de contrat
- `templates/emails/test.html.twig` - Test de configuration

#### Templates - Administration des tâches
- `templates/admin/task/index.html.twig` - Liste des tâches
- `templates/admin/task/new.html.twig` - Nouvelle tâche
- `templates/admin/task/show.html.twig` - Détails d'une tâche

#### Templates - Paramètres
- `templates/admin/settings/currency_new.html.twig` - Nouvelle devise
- `templates/admin/settings/currencies.html.twig` - Liste des devises (amélioré)
- `templates/admin/settings/payment.html.twig` - Paramètres de paiement
- `templates/admin/settings/email.html.twig` - Configuration email

#### Documentation
- `TASK_MANAGER_README.md` - Guide complet du système de tâches
- `CURRENCY_USAGE.md` - Guide d'utilisation des devises
- `FEATURES_SUMMARY.md` - Résumé des fonctionnalités
- `CHANGELOG.md` - Ce fichier

### 🔧 Modifications

#### Entités modifiées
- `src/Entity/Currency.php` :
  - Ajout du champ `decimalPlaces`
  - Ajout des méthodes `getIsDefault()`, `setIsDefault()`
  - Ajout des méthodes `getIsActive()`, `setIsActive()`
  - Méthode `formatAmount()` utilise maintenant `decimalPlaces`

#### Services modifiés
- `src/Service/CurrencyService.php` :
  - Ajout de `getCurrencyByCode()`
  - Ajout de `getActiveCurrency()`
  - Ajout de `setActiveCurrency()`

#### Contrôleurs modifiés
- `src/Controller/Admin/SettingsController.php` :
  - Route `/devises/{id}/active` pour définir la devise active

#### Templates modifiés
- `templates/admin/maintenance.html.twig` :
  - Utilisation des fonctions de `SystemExtension`
  - Remplacement de `number_format` par `round`

### 🗄️ Migrations de base de données

Deux nouvelles migrations créées :
1. `Version20251011222226.php` - Ajout de la table `task`
2. `Version20251011222646.php` - Ajout du champ `decimal_places` à `currency`

### 📋 Tâches automatiques par défaut

4 tâches créées automatiquement :

1. **Envoi automatique des quittances de loyer**
   - Type: `RENT_RECEIPT`
   - Fréquence: Mensuelle (5ème jour)
   - Envoie les quittances pour le mois précédent

2. **Rappels de paiement automatiques**
   - Type: `PAYMENT_REMINDER`
   - Fréquence: Hebdomadaire
   - Envoie des rappels aux locataires en retard

3. **Alertes d'expiration de contrats**
   - Type: `LEASE_EXPIRATION`
   - Fréquence: Mensuelle
   - Alerte 60 jours avant l'expiration

4. **Génération automatique des loyers**
   - Type: `GENERATE_RENTS`
   - Fréquence: Mensuelle (25ème jour)
   - Crée les échéances du mois suivant

### 🎯 Commandes disponibles

```bash
# Tâches automatisées
php bin/console app:tasks:run
php bin/console app:tasks:run --task-id=1

# Envoi de quittances
php bin/console app:send-rent-receipts
php bin/console app:send-rent-receipts --month=2025-10
php bin/console app:send-rent-receipts --dry-run

# Base de données
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# Cache
php bin/console cache:clear
```

### 🔗 Routes ajoutées

#### Administration des tâches
- `GET /admin/taches` - Liste des tâches
- `GET /admin/taches/nouvelle` - Créer une tâche
- `GET /admin/taches/{id}` - Détails d'une tâche
- `POST /admin/taches/{id}/executer` - Exécuter une tâche
- `POST /admin/taches/{id}/toggle` - Activer/désactiver
- `POST /admin/taches/executer-toutes` - Exécuter les tâches dues
- `POST /admin/taches/initialiser` - Créer les tâches par défaut
- `POST /admin/taches/test-email` - Tester la configuration email
- `POST /admin/taches/envoyer-quittances` - Envoi manuel de quittances

#### Gestion des devises
- `POST /admin/parametres/devises/{id}/active` - Définir la devise active

### 🎨 Filtres et fonctions Twig

#### CurrencyExtension
```twig
{# Filtres #}
{{ montant|currency }}
{{ montant|currency(false) }}
{{ ''|currency_symbol }}

{# Fonctions #}
{{ default_currency() }}
{{ format_amount(montant) }}
{{ format_amount(montant, 'USD') }}
{{ active_currencies() }}
```

#### SystemExtension
```twig
{{ disk_free_space('.') }}
{{ disk_total_space('.') }}
{{ memory_get_usage() }}
{{ memory_get_peak_usage() }}
{{ php_version() }}
{{ ini_get('memory_limit') }}
```

### 🐛 Corrections de bugs

- ✅ Correction des méthodes `setDefault()` → `setIsDefault()` dans `Currency`
- ✅ Correction des méthodes `setActive()` → `setIsActive()` dans `Currency`
- ✅ Correction de l'utilisation de `disk_free_space()` dans les templates
- ✅ Correction de l'utilisation de `memory_get_usage()` dans les templates
- ✅ Remplacement de `number_format` par `round` dans les templates Twig
- ✅ Ajout de la méthode `getEntityManager()` dans `TaskManagerService`

### 📦 Dépendances

Aucune nouvelle dépendance externe requise. Le système utilise uniquement :
- Symfony Mailer (déjà présent)
- Doctrine ORM (déjà présent)
- Twig (déjà présent)

### 🚀 Mise en production

#### Étapes de déploiement

1. **Mettre à jour le code** :
```bash
git pull origin master
```

2. **Installer les dépendances** :
```bash
composer install --no-dev --optimize-autoloader
```

3. **Exécuter les migrations** :
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

4. **Vider le cache** :
```bash
php bin/console cache:clear --env=prod
```

5. **Initialiser les devises et tâches** :
```bash
# Via l'interface web : Administration > Tâches > Initialiser
# Ou en ligne de commande (à créer si nécessaire)
```

6. **Configurer le CRON** :
```bash
# Ajouter au crontab
0 * * * * cd /path/to/mylocca && php bin/console app:tasks:run
```

7. **Configurer les paramètres SMTP** :
- Accéder à Administration > Paramètres > Email
- Renseigner les informations SMTP
- Tester la configuration

8. **Définir la devise active** :
- Accéder à Administration > Paramètres > Devises
- Cliquer sur ✓ pour la devise souhaitée

### ⚠️ Notes importantes

1. **Configuration email** : Nécessaire pour l'envoi des notifications
2. **CRON** : Indispensable pour l'exécution automatique des tâches
3. **Devise active** : Doit être définie pour un affichage correct
4. **Permissions** : S'assurer que PHP peut écrire dans `var/log/` et `var/cache/`

### 🎉 Résumé

Cette version 2.0 apporte des fonctionnalités majeures au système MYLOCCA :
- **Automatisation complète** des tâches récurrentes
- **Notifications par email** professionnelles et personnalisables
- **Gestion multi-devises** intuitive et automatique
- **Interface d'administration** complète et ergonomique

Le système est maintenant **100% opérationnel** et prêt pour la production ! 🚀

---

## Version précédente

### Version 1.0 - Fonctionnalités de base
- Gestion des propriétés
- Gestion des locataires  
- Gestion des baux
- Gestion des paiements
- Comptabilité
- Demandes de maintenance
- Gestion documentaire
- Dashboard et statistiques
- Interface utilisateur complète

---

**Date de release** : 11 Octobre 2025  
**Développeur** : Assistant AI  
**Statut** : ✅ Production Ready

