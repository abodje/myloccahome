# ✅ Checklist d'installation - MYLOCCA v2.0

## 📋 Vérification de l'installation

### 1. Base de données

- [ ] Base de données créée : `myloccahomz`
- [ ] Migrations exécutées : `php bin/console doctrine:migrations:migrate`
- [ ] Fixtures chargées (optionnel) : `php bin/console doctrine:fixtures:load`

**Vérification** :
```bash
php bin/console doctrine:migrations:status
```

### 2. Cache

- [x] Cache vidé : `php bin/console cache:clear`
- [x] Cache réchauffé : `php bin/console cache:warmup`

**Statut** : ✅ Fait

### 3. Fichiers créés

#### Entités
- [x] `src/Entity/Task.php`
- [x] `src/Entity/Currency.php` (modifié avec `decimalPlaces`)

#### Services
- [x] `src/Service/NotificationService.php`
- [x] `src/Service/TaskManagerService.php`
- [x] `src/Service/CurrencyService.php` (amélioré)

#### Repositories
- [x] `src/Repository/TaskRepository.php`

#### Commandes
- [x] `src/Command/TaskRunnerCommand.php`
- [x] `src/Command/SendRentReceiptsCommand.php`

#### Contrôleurs
- [x] `src/Controller/Admin/TaskController.php`
- [x] `src/Controller/Admin/SettingsController.php` (amélioré)

#### Formulaires
- [x] `src/Form/TaskType.php`
- [x] `src/Form/CurrencyType.php` (amélioré)

#### Extensions Twig
- [x] `src/Twig/CurrencyExtension.php`
- [x] `src/Twig/SystemExtension.php`

#### Templates - Emails
- [x] `templates/emails/rent_receipt.html.twig`
- [x] `templates/emails/payment_reminder.html.twig`
- [x] `templates/emails/lease_expiration.html.twig`
- [x] `templates/emails/test.html.twig`

#### Templates - Tâches
- [x] `templates/admin/task/index.html.twig`
- [x] `templates/admin/task/new.html.twig`
- [x] `templates/admin/task/show.html.twig`

#### Templates - Paramètres
- [x] `templates/admin/settings/currency_new.html.twig`
- [x] `templates/admin/settings/currencies.html.twig` (amélioré)
- [x] `templates/admin/settings/payment.html.twig`
- [x] `templates/admin/settings/email.html.twig`

#### Documentation
- [x] `TASK_MANAGER_README.md`
- [x] `CURRENCY_USAGE.md`
- [x] `FEATURES_SUMMARY.md`
- [x] `CHANGELOG.md`
- [x] `INSTALLATION_CHECKLIST.md` (ce fichier)

### 4. Configuration

#### Devises
- [ ] Accéder à `/admin/parametres/devises`
- [ ] Vérifier que les devises sont présentes
- [ ] Définir la devise active (bouton ✓)

**Test** :
```bash
# Tester l'affichage dans n'importe quelle page avec des montants
```

#### Email (SMTP)
- [ ] Accéder à `/admin/parametres/email`
- [ ] Configurer les paramètres SMTP :
  - [ ] Hôte SMTP
  - [ ] Port (587 recommandé)
  - [ ] Nom d'utilisateur
  - [ ] Mot de passe
  - [ ] Chiffrement (TLS recommandé)
  - [ ] Email expéditeur
  - [ ] Nom de l'expéditeur
- [ ] Tester la configuration via `/admin/taches` (section "Test de configuration email")

**Vérification** :
```bash
# Un email de test doit être reçu
```

#### Paramètres de paiement
- [ ] Accéder à `/admin/parametres/paiements`
- [ ] Configurer :
  - [ ] Jour d'échéance par défaut
  - [ ] Délai de rappel
  - [ ] Taux de pénalité
  - [ ] Montant minimum
  - [ ] Paiements partiels (activé/désactivé)
  - [ ] Génération automatique (activé/désactivé)

### 5. Tâches automatisées

#### Initialisation
- [ ] Accéder à `/admin/taches`
- [ ] Cliquer sur "Initialiser" pour créer les 4 tâches par défaut
- [ ] Vérifier que les tâches sont actives

**Tâches créées** :
1. ✅ Envoi automatique des quittances de loyer
2. ✅ Rappels de paiement automatiques
3. ✅ Alertes d'expiration de contrats
4. ✅ Génération automatique des loyers

#### Test manuel
```bash
# Tester l'exécution des tâches
php bin/console app:tasks:run

# Tester l'envoi de quittances (simulation)
php bin/console app:send-rent-receipts --dry-run --month=2025-10
```

### 6. Configuration CRON (Production)

#### Linux/Mac
```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne (adapter le chemin)
0 * * * * cd /path/to/mylocca && php bin/console app:tasks:run >> /var/log/mylocca-tasks.log 2>&1
```

#### Windows
1. Ouvrir le Planificateur de tâches Windows
2. Créer une nouvelle tâche
3. Déclencheur : Toutes les heures
4. Action : 
   - Programme : `C:\wamp64\bin\php\php8.x.x\php.exe`
   - Arguments : `C:\wamp64\mylocca\bin\console app:tasks:run`

### 7. Tests fonctionnels

#### Test de la devise
- [ ] Accéder au dashboard
- [ ] Vérifier que tous les montants s'affichent avec la devise active
- [ ] Changer la devise active
- [ ] Recharger la page
- [ ] Vérifier que les montants utilisent la nouvelle devise

**Pages à tester** :
- Dashboard (`/`)
- Mes biens (`/biens`)
- Paiements (`/paiements`)
- Comptabilité (`/comptabilite`)

#### Test des emails
- [ ] Envoyer un email de test via `/admin/taches`
- [ ] Vérifier la réception
- [ ] Vérifier le formatage HTML
- [ ] Vérifier que les informations sont correctes

#### Test des tâches
- [ ] Créer une nouvelle tâche via `/admin/taches/nouvelle`
- [ ] Exécuter la tâche manuellement
- [ ] Vérifier les statistiques
- [ ] Activer/désactiver la tâche
- [ ] Consulter les détails

#### Test d'envoi de quittances
- [ ] S'assurer d'avoir des paiements marqués comme "Payé"
- [ ] Accéder à `/admin/taches`
- [ ] Section "Envoi manuel de quittances"
- [ ] Sélectionner un mois
- [ ] Envoyer
- [ ] Vérifier les emails reçus

### 8. Vérification des routes

Tester l'accès aux pages principales :

#### Routes utilisateur
- [ ] `/` - Dashboard
- [ ] `/biens` - Mes biens
- [ ] `/locataires` - Gestion des locataires
- [ ] `/baux` - Gestion des baux
- [ ] `/paiements` - Historique des paiements
- [ ] `/comptabilite` - Comptabilité
- [ ] `/demandes` - Demandes de maintenance
- [ ] `/documents` - Mes documents
- [ ] `/profil` - Mon profil

#### Routes administration
- [ ] `/admin` - Dashboard admin
- [ ] `/admin/parametres` - Paramètres généraux
- [ ] `/admin/parametres/devises` - Gestion des devises
- [ ] `/admin/parametres/email` - Configuration email
- [ ] `/admin/parametres/paiements` - Paramètres de paiement
- [ ] `/admin/taches` - Gestion des tâches

### 9. Vérification des logs

```bash
# Vérifier les logs pour détecter d'éventuelles erreurs
tail -f var/log/dev.log

# Sur Windows (PowerShell)
Get-Content var/log/dev.log -Tail 50 -Wait
```

### 10. Performance

- [ ] Temps de chargement < 2s pour le dashboard
- [ ] Pas d'erreurs dans les logs
- [ ] Pas de requêtes N+1 (vérifier avec Symfony Profiler)

### 11. Sécurité

- [ ] `.env` et `.env.local` ne sont pas commités
- [ ] Les mots de passe SMTP sont sécurisés
- [ ] Les permissions des fichiers sont correctes
- [ ] Le mode debug est désactivé en production

### 12. Documentation

- [x] README principal
- [x] Guide du système de tâches
- [x] Guide d'utilisation des devises
- [x] Résumé des fonctionnalités
- [x] Changelog
- [x] Checklist d'installation

## 🚀 Démarrage rapide

### Commandes essentielles

```bash
# 1. Installer les dépendances
composer install

# 2. Créer la base de données
php bin/console doctrine:database:create

# 3. Exécuter les migrations
php bin/console doctrine:migrations:migrate

# 4. (Optionnel) Charger les données de test
php bin/console doctrine:fixtures:load

# 5. Vider le cache
php bin/console cache:clear

# 6. Démarrer le serveur
php -S localhost:8000 -t public/
```

### Configuration minimale

1. **Devise** : Définir la devise active dans `/admin/parametres/devises`
2. **Email** : Configurer SMTP dans `/admin/parametres/email`
3. **Tâches** : Initialiser les tâches dans `/admin/taches`
4. **CRON** : Configurer le cron pour l'exécution automatique

## ✅ Critères de validation

L'installation est réussie si :

1. ✅ Toutes les pages se chargent sans erreur
2. ✅ Les montants s'affichent avec la devise active
3. ✅ Les emails de test sont reçus
4. ✅ Les tâches peuvent être exécutées manuellement
5. ✅ Aucune erreur dans les logs
6. ✅ Le dashboard affiche les statistiques correctement

## 🆘 En cas de problème

### Cache
```bash
php bin/console cache:clear
php bin/console cache:warmup
```

### Permissions (Linux/Mac)
```bash
chmod -R 777 var/
```

### Permissions (Windows)
Vérifier que l'utilisateur a les droits en écriture sur `var/`

### Base de données
```bash
# Réinitialiser complètement
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

### Routes
```bash
# Lister toutes les routes
php bin/console debug:router

# Vérifier une route spécifique
php bin/console debug:router app_admin_task_show
```

### Templates
```bash
# Vérifier qu'un template existe
ls -la templates/admin/task/show.html.twig  # Linux/Mac
dir templates\admin\task\show.html.twig      # Windows
```

## 📞 Support

En cas de problème persistant :
1. Vérifier les logs : `var/log/dev.log`
2. Consulter la documentation
3. Vérifier le changelog
4. Contacter le support technique

---

**Version** : 2.0  
**Date** : 11 Octobre 2025  
**Statut** : ✅ Prêt pour la production

