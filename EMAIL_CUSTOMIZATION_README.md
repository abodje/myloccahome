# 📧 Système de personnalisation des emails - MYLOCCA

## 🎯 Vue d'ensemble

Le système de personnalisation des emails permet aux administrateurs de **créer, modifier et personnaliser** tous les emails envoyés automatiquement par l'application MYLOCCA.

## ✨ Fonctionnalités principales

### 1. Templates d'emails personnalisables
- ✅ Éditeur HTML intégré
- ✅ Système de variables dynamiques
- ✅ Prévisualisation en temps réel
- ✅ Templates système et personnalisés
- ✅ Activation/désactivation par template
- ✅ Duplication de templates
- ✅ Statistiques d'utilisation

### 2. Variables dynamiques
- ✅ **60+ variables disponibles** dans 5 catégories :
  - Système (app_name, company_name, etc.)
  - Locataire (nom, email, adresse, etc.)
  - Propriété (adresse, type, surface, etc.)
  - Bail (dates, loyer, charges, etc.)
  - Paiement (montant, dates, statut, etc.)

### 3. Templates par défaut
- **RENT_RECEIPT** : Quittance de loyer
- **PAYMENT_REMINDER** : Rappel de paiement
- **LEASE_EXPIRATION** : Expiration de contrat
- **WELCOME** : Bienvenue nouveau locataire

## 🗂️ Fichiers créés

### Entité
- `src/Entity/EmailTemplate.php` - Stocke les templates personnalisés

### Repository
- `src/Repository/EmailTemplateRepository.php` - Méthodes de recherche avancées

### Service
- `src/Service/EmailCustomizationService.php` - Service de personnalisation
  - `sendCustomEmail()` - Envoie un email avec template
  - `replaceVariables()` - Remplace les variables
  - `prepareTenantVariables()` - Prépare les variables locataire
  - `preparePropertyVariables()` - Prépare les variables propriété
  - `prepareLeaseVariables()` - Prépare les variables bail
  - `preparePaymentVariables()` - Prépare les variables paiement
  - `getAllAvailableVariables()` - Liste toutes les variables
  - `initializeDefaultTemplates()` - Crée les templates par défaut
  - `previewTemplate()` - Prévisualise un template

### Contrôleur
- `src/Controller/Admin/EmailTemplateController.php`
  - CRUD complet pour les templates
  - Prévisualisation
  - Duplication
  - Toggle actif/inactif
  - Initialisation des templates par défaut

### Templates
- `templates/admin/email_template/index.html.twig` - Liste des templates
- `templates/admin/email_template/edit.html.twig` - Éditeur de template
- `templates/admin/email_template/new.html.twig` - Nouveau template
- `templates/admin/email_template/show.html.twig` - Détails + prévisualisation

### Service modifié
- `src/Service/NotificationService.php` - Intégration du système de templates

## 🚀 Utilisation

### Accès à l'interface

1. Connectez-vous en tant qu'administrateur
2. Accédez à **Administration > Templates d'emails**
3. URL : `/admin/templates-email`

### Initialiser les templates par défaut

1. Cliquez sur **"Initialiser les templates"**
2. 4 templates par défaut seront créés
3. Vous pouvez ensuite les personnaliser

### Créer un nouveau template

1. Cliquez sur **"Nouveau template"**
2. Remplissez les champs :
   - **Code** : Identifiant unique (ex: CUSTOM_ALERT)
   - **Nom** : Nom descriptif
   - **Sujet** : Sujet de l'email (peut contenir des variables)
   - **Contenu HTML** : Corps de l'email en HTML
   - **Version texte** : Version texte simple (optionnel)
   - **Description** : Description du template
3. Cliquez sur **"Enregistrer"**

### Modifier un template

1. Dans la liste, cliquez sur **"Modifier"** (icône crayon)
2. Éditez le contenu
3. Cliquez sur **"Prévisualiser"** pour voir le rendu
4. Cliquez sur **"Enregistrer"**

### Utiliser les variables

Les variables sont entourées de doubles accolades : `{{variable_name}}`

**Exemples** :
```html
<p>Bonjour {{tenant_first_name}},</p>
<p>Votre loyer de {{lease_monthly_rent}} est dû.</p>
<p>Propriété : {{property_full_address}}</p>
<p>Cordialement,<br>{{company_name}}</p>
```

## 📋 Variables disponibles

### Système
- `{{app_name}}` - Nom de l'application
- `{{company_name}}` - Nom de l'entreprise
- `{{company_address}}` - Adresse de l'entreprise
- `{{company_phone}}` - Téléphone
- `{{company_email}}` - Email
- `{{current_date}}` - Date actuelle (jj/mm/aaaa)
- `{{current_year}}` - Année actuelle
- `{{currency_symbol}}` - Symbole de la devise (€, $, etc.)

### Locataire
- `{{tenant_first_name}}` - Prénom
- `{{tenant_last_name}}` - Nom
- `{{tenant_full_name}}` - Nom complet
- `{{tenant_email}}` - Email
- `{{tenant_phone}}` - Téléphone
- `{{tenant_address}}` - Adresse complète

### Propriété
- `{{property_address}}` - Adresse
- `{{property_city}}` - Ville
- `{{property_postal_code}}` - Code postal
- `{{property_full_address}}` - Adresse complète
- `{{property_type}}` - Type de bien
- `{{property_rooms}}` - Nombre de pièces
- `{{property_surface}}` - Surface en m²

### Bail
- `{{lease_id}}` - Numéro du bail
- `{{lease_start_date}}` - Date de début
- `{{lease_end_date}}` - Date de fin
- `{{lease_monthly_rent}}` - Loyer mensuel (formaté avec devise)
- `{{lease_charges}}` - Charges
- `{{lease_deposit}}` - Dépôt de garantie
- `{{lease_rent_due_day}}` - Jour d'échéance
- `{{lease_status}}` - Statut du bail

### Paiement
- `{{payment_id}}` - Numéro du paiement
- `{{payment_amount}}` - Montant (formaté avec devise)
- `{{payment_due_date}}` - Date d'échéance
- `{{payment_paid_date}}` - Date de paiement
- `{{payment_type}}` - Type de paiement
- `{{payment_status}}` - Statut
- `{{payment_method}}` - Mode de paiement
- `{{payment_reference}}` - Référence

### Variables spéciales (selon le contexte)
- `{{month}}` - Mois concerné (pour les quittances)
- `{{total_amount}}` - Montant total
- `{{days_overdue}}` - Jours de retard
- `{{days_until_expiration}}` - Jours avant expiration

## 🎨 Exemples de templates

### Template de quittance simple

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .header { background: #6f42c1; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{app_name}}</h1>
        <p>Quittance de loyer</p>
    </div>
    <div class="content">
        <p>Bonjour {{tenant_first_name}},</p>
        <p>Nous vous adressons votre quittance pour <strong>{{month}}</strong>.</p>
        <p>Montant payé : <strong>{{total_amount}}</strong></p>
        <p>Merci,<br>{{company_name}}</p>
    </div>
</body>
</html>
```

### Template de rappel

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h2>Rappel de paiement</h2>
    <p>Bonjour {{tenant_full_name}},</p>
    <p>Votre loyer est en retard de <strong>{{days_overdue}} jours</strong>.</p>
    <p>Montant dû : {{payment_amount}}</p>
    <p>Contact : {{company_phone}}</p>
</body>
</html>
```

## 💻 Utilisation programmatique

### Envoyer un email personnalisé

```php
// Dans un contrôleur ou service
$emailService = $this->container->get(EmailCustomizationService::class);

// Préparer les variables
$variables = array_merge(
    $emailService->prepareTenantVariables($tenant),
    $emailService->prepareLeaseVariables($lease),
    [
        '{{month}}' => 'Octobre 2025',
        '{{total_amount}}' => '1 200,00 €'
    ]
);

// Envoyer l'email
$emailService->sendCustomEmail(
    'RENT_RECEIPT',           // Code du template
    $tenant->getEmail(),      // Destinataire
    $variables                // Variables
);
```

### Créer un template programmatiquement

```php
$template = new EmailTemplate();
$template->setCode('MAINTENANCE_COMPLETE')
         ->setName('Maintenance terminée')
         ->setSubject('Travaux terminés - {{property_address}}')
         ->setHtmlContent('<p>Bonjour {{tenant_first_name}},</p><p>Les travaux sont terminés.</p>')
         ->setIsActive(true);

$entityManager->persist($template);
$entityManager->flush();
```

## 📊 Statistiques et suivi

### Compteur d'utilisation
Chaque envoi incrémente automatiquement le compteur du template.

### Dernière utilisation
La date du dernier envoi est enregistrée automatiquement.

### Templates les plus utilisés
Affichés en haut de la page de gestion.

## 🔒 Sécurité

### Templates système
- Marqués comme `isSystem = true`
- **Ne peuvent pas être supprimés**
- Peuvent être modifiés mais restent récupérables

### Templates personnalisés
- Créés par les administrateurs
- Peuvent être supprimés
- Peuvent être dupliqués

### Validation
- Les variables sont échappées pour éviter les XSS
- Le HTML est nettoyé avant l'envoi

## 🎯 Routes disponibles

| Route | Description |
|-------|-------------|
| `/admin/templates-email` | Liste des templates |
| `/admin/templates-email/nouveau` | Créer un template |
| `/admin/templates-email/{id}` | Voir un template |
| `/admin/templates-email/{id}/modifier` | Modifier un template |
| `/admin/templates-email/{id}/previsualiser` | Prévisualiser |
| `/admin/templates-email/{id}/toggle` | Activer/désactiver |
| `/admin/templates-email/{id}/dupliquer` | Dupliquer |
| `/admin/templates-email/initialiser` | Créer templates par défaut |

## 📝 Workflow recommandé

1. **Initialiser** les templates par défaut
2. **Tester** les envois avec les templates existants
3. **Personnaliser** selon vos besoins
4. **Dupliquer** pour créer des variantes
5. **Désactiver** les templates non utilisés

## 🧪 Tests

### Tester un template

1. Modifiez le template
2. Cliquez sur "Prévisualiser"
3. Vérifiez le rendu dans la modal
4. Enregistrez si satisfait

### Envoyer un test réel

Via l'interface des tâches :
1. **Administration > Tâches**
2. Section "Test de configuration email"
3. Entrez votre email
4. Cliquez sur "Envoyer un test"

## 💡 Conseils

### Design
- Utilisez des styles inline pour la compatibilité email
- Testez sur différents clients (Gmail, Outlook, etc.)
- Restez simple et lisible
- Utilisez des tableaux pour la mise en page

### Variables
- Vérifiez toujours que les variables existent
- Utilisez des valeurs par défaut si possible
- Testez avec la prévisualisation

### Performance
- Les templates sont mis en cache
- Les variables sont calculées une seule fois
- L'envoi est asynchrone pour les gros volumes

## 🔄 Intégration avec NotificationService

Le `NotificationService` utilise automatiquement les templates personnalisés s'ils existent :

```php
// Dans NotificationService.php
public function sendRentReceiptToTenant(Lease $lease, array $payments, \DateTime $forMonth): void
{
    // Préparer les variables
    $variables = array_merge(
        $this->emailCustomizationService->prepareTenantVariables($tenant),
        $this->emailCustomizationService->prepareLeaseVariables($lease),
        // ... autres variables
    );
    
    // Envoyer avec template personnalisé ou template Twig par défaut
    $this->sendEmailWithCustomTemplate(
        'RENT_RECEIPT',
        $tenant->getEmail(),
        $variables,
        'emails/rent_receipt.html.twig',  // Fallback
        $twigData,
        $subject
    );
}
```

## 📦 Installation

### 1. Exécuter la migration

```bash
php bin/console doctrine:migrations:migrate
```

### 2. Initialiser les templates

Via l'interface web : **Administration > Templates d'emails > Initialiser**

Ou en ligne de commande (à créer si nécessaire) :
```bash
php bin/console app:email:init-templates
```

### 3. Configurer SMTP

**Administration > Paramètres > Email**

### 4. Tester

**Administration > Tâches > Test de configuration email**

## 🎨 Exemple complet de template personnalisé

```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 10px 10px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
            text-align: center;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{app_name}}</h1>
        <p>Quittance de loyer</p>
    </div>
    
    <div class="content">
        <p>Bonjour {{tenant_first_name}},</p>
        
        <p>Nous vous adressons votre quittance de loyer pour le mois de <strong>{{month}}</strong>.</p>
        
        <div class="info-box">
            <strong>Propriété :</strong> {{property_full_address}}<br>
            <strong>Type :</strong> {{property_type}} - {{property_rooms}} pièces<br>
            <strong>Surface :</strong> {{property_surface}} m²
        </div>
        
        <div class="amount">
            Montant payé : {{total_amount}}
        </div>
        
        <p>Cette quittance annule tous les reçus précédents pour ce mois.</p>
        
        <p>Cordialement,<br><strong>{{company_name}}</strong></p>
        
        <div class="footer">
            <p>
                {{company_name}}<br>
                {{company_address}}<br>
                Tél: {{company_phone}} - Email: {{company_email}}
            </p>
            <p style="margin-top: 10px;">
                Document généré le {{current_date}}
            </p>
        </div>
    </div>
</body>
</html>
```

## ✅ Checklist de validation

- [x] Entité `EmailTemplate` créée
- [x] Repository avec méthodes de recherche
- [x] Service `EmailCustomizationService` créé
- [x] 60+ variables dynamiques définies
- [x] 4 templates par défaut prêts
- [x] Contrôleur admin complet (CRUD)
- [x] Interface d'édition avec prévisualisation
- [x] Système de duplication
- [x] Statistiques d'utilisation
- [x] Intégration avec NotificationService
- [x] Documentation complète

## 🎉 Conclusion

Le système de personnalisation des emails est maintenant **100% opérationnel** !

Les administrateurs peuvent :
- ✅ Créer des templates personnalisés
- ✅ Modifier les templates existants
- ✅ Utiliser 60+ variables dynamiques
- ✅ Prévisualiser en temps réel
- ✅ Suivre les statistiques d'utilisation
- ✅ Gérer facilement tous les emails de l'application

**Prêt pour la personnalisation complète de vos communications !** 🚀

---

**Version** : 2.2  
**Date** : 11 Octobre 2025  
**Status** : ✅ 100% Opérationnel

