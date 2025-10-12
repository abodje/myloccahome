# Système de Gestion des Tâches et Notifications - MYLOCCA

## 📋 Vue d'ensemble

Ce document décrit le système complet de gestion des tâches automatisées et d'envoi de notifications par email mis en place dans MYLOCCA.

## 🎯 Fonctionnalités principales

### 1. Gestionnaire de tâches automatisées
- **Planification flexible** : Tâches quotidiennes, hebdomadaires, mensuelles ou personnalisées
- **Exécution automatique** : Via cron ou commande manuelle
- **Suivi des performances** : Statistiques de réussite/échec
- **Gestion des erreurs** : Logs détaillés et alertes

### 2. Service de notifications
- **Quittances de loyer** : Envoi automatique aux locataires
- **Rappels de paiement** : Pour les loyers en retard
- **Alertes d'expiration** : Pour les contrats arrivant à échéance
- **Génération automatique** : Création des loyers du mois suivant

### 3. Gestion de la devise
- **Multi-devises** : Support de plusieurs devises
- **Devise active** : Appliquée automatiquement partout dans l'application
- **Convertisseur** : Conversion entre devises
- **Formatage automatique** : Via filtres Twig

## 🗂️ Fichiers créés

### Entités

#### `src/Entity/Task.php`
Gère les tâches programmées avec :
- Type, fréquence, statut
- Dates d'exécution (dernière/prochaine)
- Compteurs de succès/échecs
- Paramètres personnalisables

### Services

#### `src/Service/NotificationService.php`
Service principal pour l'envoi de notifications :
- `sendRentReceipts()` : Envoie les quittances de loyer
- `sendPaymentReminders()` : Envoie les rappels de paiement
- `sendLeaseExpirationAlerts()` : Alertes d'expiration de contrats
- `generateNextMonthRents()` : Génère les loyers du mois suivant
- `testEmailConfiguration()` : Test de la configuration email

#### `src/Service/TaskManagerService.php`
Gestionnaire de tâches automatisées :
- `runDueTasks()` : Exécute les tâches dues
- `executeTask()` : Exécute une tâche spécifique
- `createDefaultTasks()` : Crée les tâches par défaut
- `getTaskStatistics()` : Statistiques des tâches
- `toggleTask()` : Active/désactive une tâche

#### `src/Service/CurrencyService.php`
Gestion des devises (amélioré) :
- `getActiveCurrency()` : Récupère la devise active
- `setActiveCurrency()` : Définit la devise active
- `formatAmount()` : Formate un montant avec la devise
- `convertAmount()` : Convertit entre devises

### Repositories

#### `src/Repository/TaskRepository.php`
Méthodes de requêtes pour les tâches :
- `findDueTasks()` : Tâches à exécuter
- `findByType()` : Tâches par type
- `findActive()` : Tâches actives
- `getStatistics()` : Statistiques

### Commandes

#### `src/Command/TaskRunnerCommand.php`
```bash
php bin/console app:tasks:run
```
Exécute toutes les tâches dues. Options :
- `--task-id=X` : Exécute une tâche spécifique
- `--force` : Force l'exécution

#### `src/Command/SendRentReceiptsCommand.php`
```bash
php bin/console app:send-rent-receipts --month=2025-10
```
Envoie les quittances de loyer pour un mois spécifique. Options :
- `--month=YYYY-MM` : Mois cible
- `--dry-run` : Simulation sans envoi

### Contrôleurs

#### `src/Controller/Admin/TaskController.php`
Interface web pour gérer les tâches :
- `/admin/taches` : Liste des tâches
- `/admin/taches/nouvelle` : Créer une tâche
- `/admin/taches/{id}` : Détails d'une tâche
- `/admin/taches/{id}/executer` : Exécuter une tâche
- `/admin/taches/test-email` : Test de configuration email
- `/admin/taches/envoyer-quittances` : Envoi manuel de quittances

#### `src/Controller/Admin/SettingsController.php` (amélioré)
Routes ajoutées :
- `/admin/parametres/devises/{id}/active` : Définir la devise active

### Extensions Twig

#### `src/Twig/CurrencyExtension.php`
Filtres et fonctions pour la devise :
- `{{ montant|currency }}` : Formate avec la devise active
- `{{ ''|currency_symbol }}` : Symbole de la devise active
- `{{ default_currency() }}` : Objet devise active
- `{{ format_amount(montant, 'USD') }}` : Formate avec devise spécifique

#### `src/Twig/SystemExtension.php`
Fonctions système pour les templates :
- `{{ disk_free_space('.') }}` : Espace disque libre
- `{{ memory_get_usage() }}` : Mémoire utilisée
- `{{ php_version() }}` : Version PHP
- `{{ ini_get('option') }}` : Configuration PHP

### Templates

#### Templates d'emails
- `templates/emails/rent_receipt.html.twig` : Quittance de loyer
- `templates/emails/payment_reminder.html.twig` : Rappel de paiement
- `templates/emails/lease_expiration.html.twig` : Expiration de contrat
- `templates/emails/test.html.twig` : Test de configuration

#### Templates d'administration
- `templates/admin/task/index.html.twig` : Gestion des tâches
- `templates/admin/settings/currency_new.html.twig` : Nouvelle devise
- `templates/admin/settings/currencies.html.twig` : Liste des devises (amélioré)
- `templates/admin/settings/payment.html.twig` : Paramètres de paiement
- `templates/admin/settings/email.html.twig` : Configuration email

### Formulaires

#### `src/Form/CurrencyType.php` (amélioré)
Ajout du champ `decimalPlaces` pour définir le nombre de décimales.

## 🚀 Installation et configuration

### 1. Mise à jour de la base de données

```bash
php bin/console doctrine:migrations:migrate
```

### 2. Initialisation des tâches par défaut

Via l'interface web : **Administration > Tâches > Initialiser**

Ou en ligne de commande :
```bash
php bin/console app:tasks:run
```

### 3. Configuration de la devise

1. Accédez à **Administration > Paramètres > Devises**
2. Cliquez sur **✓** pour définir la devise active
3. La devise sera appliquée partout dans l'application

### 4. Configuration Email

1. Accédez à **Administration > Paramètres > Email**
2. Configurez les paramètres SMTP
3. Testez la configuration via **Administration > Tâches**

## ⏰ Configuration des tâches CRON

### Linux/Mac

Ajoutez au crontab (`crontab -e`) :

```bash
# Exécuter les tâches dues toutes les heures
0 * * * * cd /chemin/vers/mylocca && php bin/console app:tasks:run >> /var/log/mylocca-tasks.log 2>&1

# Envoi des quittances le 5 de chaque mois
0 9 5 * * cd /chemin/vers/mylocca && php bin/console app:send-rent-receipts --month=$(date -d "last month" +%Y-%m) >> /var/log/mylocca-receipts.log 2>&1
```

### Windows

Utilisez le **Planificateur de tâches Windows** :

1. Ouvrez le Planificateur de tâches
2. Créez une nouvelle tâche
3. Déclencheur : Toutes les heures (ou selon vos besoins)
4. Action : Démarrer un programme
   - Programme : `C:\wamp64\bin\php\php8.x.x\php.exe`
   - Arguments : `C:\wamp64\mylocca\bin\console app:tasks:run`

## 📝 Tâches par défaut créées

### 1. Envoi automatique des quittances de loyer
- **Type** : `RENT_RECEIPT`
- **Fréquence** : Mensuelle
- **Description** : Envoie les quittances aux locataires ayant payé
- **Paramètres** :
  - `day_of_month`: 5 (5ème jour du mois)
  - `month_offset`: '-1 month' (pour le mois précédent)

### 2. Rappels de paiement automatiques
- **Type** : `PAYMENT_REMINDER`
- **Fréquence** : Hebdomadaire
- **Description** : Envoie des rappels aux locataires en retard
- **Paramètres** :
  - `min_days_overdue`: 3

### 3. Alertes d'expiration de contrats
- **Type** : `LEASE_EXPIRATION`
- **Fréquence** : Mensuelle
- **Description** : Alerte les locataires dont le contrat expire bientôt
- **Paramètres** :
  - `days_before_expiration`: 60

### 4. Génération automatique des loyers
- **Type** : `GENERATE_RENTS`
- **Fréquence** : Mensuelle
- **Description** : Génère les échéances du mois suivant
- **Paramètres** :
  - `day_of_month`: 25 (25ème jour du mois)

## 📊 Utilisation

### Interface Web

#### Gérer les tâches
1. **Administration > Tâches**
2. Voir la liste de toutes les tâches
3. Actions disponibles :
   - ▶️ Exécuter maintenant
   - ⏸️ Activer/Désactiver
   - 👁️ Voir les détails

#### Envoyer des quittances manuellement
1. **Administration > Tâches**
2. Section "Envoi manuel de quittances"
3. Sélectionnez le mois
4. Cliquez sur "Envoyer"

#### Tester la configuration email
1. **Administration > Tâches**
2. Section "Test de configuration email"
3. Entrez votre adresse email
4. Cliquez sur "Envoyer un test"

### Ligne de commande

#### Exécuter toutes les tâches dues
```bash
php bin/console app:tasks:run
```

#### Exécuter une tâche spécifique
```bash
php bin/console app:tasks:run --task-id=1
```

#### Envoyer les quittances du mois dernier
```bash
php bin/console app:send-rent-receipts --month=$(date -d "last month" +%Y-%m)
```

#### Simulation (dry-run)
```bash
php bin/console app:send-rent-receipts --month=2025-10 --dry-run
```

## 🎨 Utilisation de la devise dans les templates

### Exemples de base

```twig
{# Formate avec la devise active #}
Le loyer est de {{ property.monthlyRent|currency }}

{# Formate sans symbole #}
Montant: {{ payment.amount|currency(false) }}

{# Récupère le symbole #}
Total en {{ ''|currency_symbol }}

{# Formate avec une devise spécifique #}
Prix en USD: {{ format_amount(price, 'USD') }}
```

### Dans les formulaires

```twig
<div class="input-group">
    {{ form_widget(form.amount, {'class': 'form-control'}) }}
    <span class="input-group-text">{{ ''|currency_symbol }}</span>
</div>
```

## 🔧 Personnalisation

### Créer une nouvelle tâche

1. Ajouter un nouveau type dans `TaskManagerService`
2. Créer la méthode d'exécution
3. Ajouter la tâche dans `createDefaultTasks()`

Exemple :

```php
private function executeCustomTask(Task $task): void
{
    // Votre logique ici
    $results = $this->customService->doSomething();
    
    $task->setParameter('last_result', $results);
}
```

### Créer un nouveau type d'email

1. Créer le template dans `templates/emails/`
2. Ajouter la méthode dans `NotificationService`
3. L'intégrer dans une tâche si nécessaire

## 📈 Monitoring et logs

### Logs applicatifs
Les logs sont dans `var/log/dev.log` (ou `prod.log` en production)

### Logs des tâches
- Succès/échecs enregistrés dans la base de données
- Consultables via **Administration > Tâches**
- Statistiques en temps réel

### Alertes
- Les échecs récents sont affichés en haut de la page des tâches
- Emails d'alerte (à configurer dans les paramètres)

## 🛟 Dépannage

### Les emails ne partent pas
1. Vérifier la configuration SMTP dans **Administration > Paramètres > Email**
2. Tester avec **Administration > Tâches > Test de configuration email**
3. Consulter les logs : `var/log/dev.log`
4. Vérifier que `email_notifications` est activé

### Les tâches ne s'exécutent pas
1. Vérifier que le cron est configuré
2. Exécuter manuellement : `php bin/console app:tasks:run`
3. Vérifier que les tâches sont actives
4. Consulter les logs d'erreur dans la base de données

### La devise ne s'applique pas
1. Vider le cache : `php bin/console cache:clear`
2. Vérifier qu'une devise est définie comme active
3. S'assurer d'utiliser le filtre `|currency` dans les templates

## 📚 Documentation additionnelle

- **CURRENCY_USAGE.md** : Guide complet d'utilisation des devises
- **composer.json** : Dépendances du projet
- **migrations/** : Historique des modifications de la base de données

## 🎉 Conclusion

Le système de gestion des tâches et notifications de MYLOCCA est maintenant opérationnel et prêt à l'emploi. Il permet :

✅ Envoi automatique de quittances de loyer  
✅ Rappels de paiement programmés  
✅ Alertes d'expiration de contrats  
✅ Génération automatique des loyers  
✅ Gestion multi-devises complète  
✅ Interface d'administration intuitive  
✅ Monitoring et statistiques en temps réel  

Pour toute question ou assistance, consultez la documentation ou contactez le support technique.

