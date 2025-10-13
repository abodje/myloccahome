# 🚀 Transformation MYLOCCA en SaaS Multi-Tenant

## 📋 Vue d'ensemble

MYLOCCA a été transformé en une application SaaS (Software as a Service) multi-tenant avec système d'abonnement, permettant à plusieurs entreprises d'utiliser la plateforme de manière isolée et sécurisée.

---

## 🏗️ Architecture Multi-Tenant

### **Principe du Multi-Tenant**

Chaque **Organisation** a ses propres données complètement isolées :
- ✅ Utilisateurs séparés
- ✅ Propriétés séparées
- ✅ Locataires séparés
- ✅ Paiements isolés
- ✅ Documents privés

### **Structure des Entités**

```
Organization (Entreprise)
├── activeSubscription (Abonnement actif)
├── users[] (Utilisateurs)
├── properties[] (Propriétés)
├── tenants[] (Locataires)
├── leases[] (Baux)
├── payments[] (Paiements)
└── subscriptions[] (Historique abonnements)

Plan (Formule d'abonnement)
├── name (Starter, Pro, Enterprise)
├── monthlyPrice
├── yearlyPrice
├── maxProperties (Limite)
├── maxTenants (Limite)
├── features[] (Fonctionnalités)
└── subscriptions[] (Abonnements actifs)

Subscription (Abonnement d'une org à un plan)
├── organization
├── plan
├── status (ACTIVE, EXPIRED, CANCELLED)
├── billingCycle (MONTHLY, YEARLY)
├── startDate
├── endDate
└── autoRenew
```

---

## 💎 Plans d'Abonnement

### **Plan 1: Starter** 🌱

**Prix :**
- Mensuel : 9,900 FCFA/mois
- Annuel : 99,000 FCFA/an (économie de 17%)

**Limites :**
- 5 propriétés
- 10 locataires
- 2 utilisateurs
- 50 documents

**Fonctionnalités :**
- ✅ Dashboard
- ✅ Gestion propriétés
- ✅ Gestion locataires
- ✅ Gestion baux
- ✅ Suivi paiements
- ✅ Documents

**Idéal pour :**
- Particuliers avec quelques biens
- Débuter dans la gestion locative
- Petits portefeuilles

---

### **Plan 2: Professional** ⭐ (Plus Populaire)

**Prix :**
- Mensuel : 24,900 FCFA/mois
- Annuel : 249,000 FCFA/an (économie de 17%)

**Limites :**
- 20 propriétés
- 50 locataires
- 5 utilisateurs
- 200 documents

**Fonctionnalités :**
- ✅ Toutes les fonctionnalités Starter
- ✅ Comptabilité avancée
- ✅ Demandes de maintenance
- ✅ Paiements en ligne (CinetPay)
- ✅ Acomptes
- ✅ Rapports détaillés
- ✅ Notifications email

**Idéal pour :**
- Gestionnaires professionnels
- Agences immobilières
- Investisseurs actifs

---

### **Plan 3: Enterprise** 🏢

**Prix :**
- Mensuel : 49,900 FCFA/mois
- Annuel : 499,000 FCFA/an (économie de 17%)

**Limites :**
- ∞ Propriétés illimitées
- ∞ Locataires illimités
- ∞ Utilisateurs illimités
- ∞ Documents illimités

**Fonctionnalités :**
- ✅ Toutes les fonctionnalités Professional
- ✅ Notifications SMS (Orange SMS)
- ✅ Branding personnalisé
- ✅ Accès API
- ✅ Support prioritaire
- ✅ Multi-devises

**Idéal pour :**
- Grandes agences
- Promoteurs immobiliers
- Gestionnaires de patrimoine

---

## 🔄 Flux d'Inscription

### **Parcours Client**

```
1. Visiteur arrive sur /inscription/plans
   ↓
   Affichage des 3 plans (Starter, Pro, Enterprise)
   ↓
2. Clic sur "Commencer l'essai gratuit"
   ↓
   Formulaire d'inscription
   ├─ Informations entreprise
   ├─ Compte administrateur
   └─ Choix cycle (mensuel/annuel)
   ↓
3. Soumission du formulaire
   ↓
   Création:
   ├─ Organization (status: TRIAL)
   ├─ User (ROLE_ADMIN)
   └─ Subscription (status: PENDING)
   ↓
4. Redirection vers page de paiement
   (optionnel si période d'essai)
   ↓
5. Activation du compte
   ├─ Abonnement: ACTIVE
   ├─ Organisation: ACTIVE
   └─ Période d'essai: 30 jours
   ↓
6. Connexion et accès à l'application
```

---

## 🔐 Isolation des Données (Multi-Tenant)

### **Filtre Automatique**

Un EventSubscriber (`OrganizationFilterSubscriber`) applique automatiquement un filtre SQL :

```sql
-- Exemple de requête automatiquement filtrée
SELECT * FROM property WHERE organization_id = 123

-- Au lieu de
SELECT * FROM property
```

### **Entités Filtrées**

- Property
- Tenant
- Lease
- Payment
- Document
- MaintenanceRequest
- Expense
- AccountingEntry

### **Comment ça fonctionne ?**

```php
// Quand un utilisateur se connecte
$user->getOrganization() → Organization #123

// Le filtre s'active automatiquement
Toutes les requêtes sont filtrées par organization_id = 123

// L'utilisateur ne voit QUE les données de son organisation
// Impossible de voir les données d'une autre organisation
```

---

## 💳 Gestion des Abonnements

### **États d'un Abonnement**

| État | Description | Actions possibles |
|------|-------------|-------------------|
| **PENDING** | En attente de paiement | Payer, Annuler |
| **ACTIVE** | Actif et valide | Renouveler, Annuler |
| **EXPIRED** | Expiré | Renouveler |
| **CANCELLED** | Annulé par l'utilisateur | Réactiver |

### **Cycle de Vie**

```
PENDING (Création)
    ↓ (Paiement)
ACTIVE (30 jours d'essai puis facturation)
    ↓ (Fin période)
    ├─ autoRenew = true → Renouvellement auto
    ├─ autoRenew = false → EXPIRED
    └─ Annulation → CANCELLED
```

### **Renouvellement Automatique**

**Tâche planifiée** (quotidienne) :
```php
// Vérifie les abonnements qui expirent dans 7 jours
// Envoie alertes par email/SMS
// Si autoRenew = true → Déclenche paiement

$subscriptionService->checkAndExpireSubscriptions();
$subscriptionService->sendExpirationAlerts(7);
```

---

## 🎯 Vérification des Limites

### **Avant Création de Ressource**

```php
// Exemple: Vérifier si on peut ajouter une propriété
if (!$subscriptionService->canAddResource($organization, 'properties')) {
    throw new \Exception("Limite de propriétés atteinte pour votre plan. Upgradez pour continuer.");
}

// Créer la propriété
$property = new Property();
$property->setOrganization($organization);
// ...
```

### **Vérifications Automatiques**

Dans les contrôleurs `new()` :
```php
public function new(Request $request) {
    $organization = $this->getUser()->getOrganization();
    
    // Vérifier la limite
    if ($organization->getPlan()->isLimitReached('properties', $organization->getProperties()->count())) {
        $this->addFlash('warning', 'Vous avez atteint la limite de votre plan. Passez à un plan supérieur.');
        return $this->redirectToRoute('app_subscription_upgrade');
    }
    
    // Continuer la création
}
```

---

## 📊 Dashboard SaaS Admin

### **Métriques Clés (MRR, Churn, etc.)**

```php
// Dans un SuperAdminController

// 1. MRR (Monthly Recurring Revenue)
$mrr = $subscriptionRepository->getMonthlyRevenue();

// 2. Nombre total d'organisations
$totalOrgs = $organizationRepository->count([]);

// 3. Organisations actives
$activeOrgs = $organizationRepository->count(['status' => 'ACTIVE']);

// 4. Taux de conversion essai → payant
$trialOrgs = $organizationRepository->count(['status' => 'TRIAL']);
$conversionRate = ($activeOrgs / ($activeOrgs + $trialOrgs)) * 100;

// 5. Churn rate (taux d'annulation)
// À implémenter selon la période
```

---

## 🔧 Configuration

### **1. Enregistrer le Filtre Doctrine**

Dans `config/packages/doctrine.yaml` :
```yaml
doctrine:
    orm:
        filters:
            organization_filter:
                class: App\EventSubscriber\OrganizationFilter
                enabled: true
```

### **2. Créer les Plans par Défaut**

```bash
php bin/console app:create-default-plans
```

**Sortie :**
```
Création des Plans d'Abonnement Par Défaut
==========================================

 ✅ Plan 'Starter' créé
 ✅ Plan 'Professional' créé
 ✅ Plan 'Enterprise' créé

 [OK] ✨ 3 plan(s) d'abonnement créé(s)
```

### **3. Migration de Base de Données**

```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

---

## 📝 Modifications Nécessaires aux Entités Existantes

### **Ajouter `organization` à chaque entité**

**Exemple pour `Property` :**
```php
#[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'properties')]
#[ORM\JoinColumn(nullable: false)]
private ?Organization $organization = null;

public function getOrganization(): ?Organization
{
    return $this->organization;
}

public function setOrganization(?Organization $organization): static
{
    $this->organization = $organization;
    return $this;
}
```

**À faire pour :**
- User
- Property
- Tenant
- Lease
- Payment
- Document
- MaintenanceRequest
- Expense
- AccountingEntry

---

## 🎨 Branding Personnalisé (Plan Enterprise)

### **Logo Personnalisé**

```php
// Dans base.html.twig
{% if app.user.organization.logo %}
    <img src="{{ asset(app.user.organization.logo) }}" alt="Logo">
{% else %}
    <h4>MYLOCCA</h4>
{% endif %}
```

### **Nom de l'App Personnalisé**

```php
{{ app.user.organization.name }}
// Au lieu de "MYLOCCA"
```

### **Couleurs Personnalisées**

```php
// Stocker dans organization.settings
$organization->setSetting('primary_color', '#5a8db3');
$organization->setSetting('secondary_color', '#6c757d');
```

---

## 💰 Intégration Paiement Abonnement

### **Via CinetPay**

```php
// Dans RegistrationController::payment()

// Initialiser le paiement de l'abonnement
$cinetpay->setAmount($subscription->getAmount())
         ->setDescription("Abonnement {$plan->name} - {$organization->name}")
         ->setTransactionId("SUB-{$subscription->id}-" . uniqid())
         ->setCustomer([...])
         ->setMetadata([
             'subscription_id' => $subscription->getId(),
             'organization_id' => $organization->getId(),
             'type' => 'subscription_payment'
         ]);

$paymentUrl = $cinetpay->initPayment();
return $this->redirect($paymentUrl);

// Callback notification activera l'abonnement
```

---

## 📊 Fichiers Créés

### **Entités (3)**
1. ✅ `src/Entity/Organization.php`
2. ✅ `src/Entity/Plan.php`
3. ✅ `src/Entity/Subscription.php`

### **Repositories (3)**
4. ✅ `src/Repository/OrganizationRepository.php`
5. ✅ `src/Repository/PlanRepository.php`
6. ✅ `src/Repository/SubscriptionRepository.php`

### **Services (1)**
7. ✅ `src/Service/SubscriptionService.php`

### **Event Subscribers (1)**
8. ✅ `src/EventSubscriber/OrganizationFilterSubscriber.php`

### **Commandes (1)**
9. ✅ `src/Command/CreateDefaultPlansCommand.php`

### **Contrôleurs (1)**
10. ✅ `src/Controller/RegistrationController.php`

### **Templates (2)**
11. ✅ `templates/registration/plans.html.twig`
12. ✅ `templates/registration/register.html.twig`

---

## 🚀 Prochaines Étapes

### **Critiques (À faire maintenant)**

1. **Migration DB** : Ajouter `organization_id` à toutes les entités
2. **Modifier User** : Ajouter relation `organization`
3. **Configurer filtre Doctrine** : Dans `doctrine.yaml`
4. **Créer les plans** : `php bin/console app:create-default-plans`

### **Importantes (Court terme)**

5. **Page de paiement** : Intégrer CinetPay pour abonnements
6. **Gestion abonnement** : Page admin pour gérer son abonnement
7. **Upgrade/Downgrade** : Changer de plan
8. **Template `payment.html.twig`** : Page de paiement d'abonnement

### **Optionnelles (Moyen terme)**

9. **Factures** : Générer factures d'abonnement
10. **Webhooks CinetPay** : Renouvellements automatiques
11. **Dashboard SuperAdmin** : Gérer toutes les organisations
12. **Analytics** : Métriques SaaS (MRR, Churn, etc.)

---

## 💡 Avantages du Modèle SaaS

### **Pour MYLOCCA (Éditeur)**

1. 💰 **Revenus récurrents** : MRR prévisible
2. 📈 **Scalabilité** : Servir des milliers de clients
3. 🔧 **Maintenance centralisée** : Une seule app pour tous
4. 📊 **Données analytiques** : Comprendre l'utilisation
5. 🚀 **Croissance rapide** : Acquisition clients facilitée

### **Pour les Clients**

1. 💰 **Coût réduit** : Pas d'infrastructure
2. ⚡ **Démarrage rapide** : Opérationnel en 5 minutes
3. 🔄 **Mises à jour automatiques** : Toujours la dernière version
4. 📱 **Accessible partout** : Cloud-based
5. 🔐 **Sécurité** : Données chiffrées et isolées

---

## 🎯 KPIs SaaS à Suivre

### **Métriques Financières**

- **MRR** (Monthly Recurring Revenue) : Revenu mensuel récurrent
- **ARR** (Annual Recurring Revenue) : MRR × 12
- **ARPU** (Average Revenue Per User) : MRR / Nombre clients
- **LTV** (Lifetime Value) : Valeur vie client
- **CAC** (Customer Acquisition Cost) : Coût d'acquisition

### **Métriques d'Engagement**

- **Churn Rate** : Taux d'annulation
- **Retention Rate** : Taux de rétention
- **Conversion Trial → Paid** : % d'essais convertis
- **Usage metrics** : Connexions, actions, etc.

### **Métriques de Croissance**

- **Nouvelles inscriptions** : Par jour/semaine/mois
- **Upgrade rate** : % qui passent à plan supérieur
- **Downgrade rate** : % qui rétrogradent
- **Reactivation rate** : % qui reviennent après annulation

---

## 🔒 Sécurité Multi-Tenant

### **Isolation Garantie**

1. ✅ **Filtre SQL automatique** : Impossible de requêter hors organisation
2. ✅ **Vérifications contrôleur** : Double check sur organization_id
3. ✅ **Tests unitaires** : Vérifier l'isolation
4. ✅ **Audit logs** : Tracer tous les accès

### **Protection Contre**

- ❌ **Cross-tenant data leakage** : Un client voit un autre
- ❌ **Elevation de privilèges** : Devenir admin d'une autre org
- ❌ **SQL injection** : Paramètres bindés
- ❌ **Mass assignment** : Validation stricte

---

## 📞 Support

### **Documentation Utilisateur**

À créer :
- Guide de démarrage rapide
- Tutoriels vidéo
- Base de connaissances
- FAQ

### **Support Technique**

Niveaux selon plan :
- **Starter** : Email (48h)
- **Professional** : Email (24h)
- **Enterprise** : Email (4h) + Téléphone + Chat

---

**Date de transformation :** 12 octobre 2025  
**Version :** 2.0 (SaaS)  
**Statut :** 🚧 Base créée - Migrations et intégration à finaliser
