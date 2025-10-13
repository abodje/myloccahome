# 🎯 Système de Gestion des Fonctionnalités par Plan - MYLOCCA SaaS

## 📋 Vue d'ensemble

Le système de gestion des fonctionnalités permet de **contrôler précisément** ce que chaque utilisateur peut faire en fonction de son plan d'abonnement. Les fonctionnalités affichées dans les plans correspondent **EXACTEMENT** à ce qui est disponible dans l'application.

---

## 🏗️ Architecture du Système

### 1. **Service Principal : `FeatureAccessService`**

**Fichier** : `src/Service/FeatureAccessService.php`

**Responsabilités** :
- ✅ Vérifier si une organisation a accès à une fonctionnalité
- ✅ Gérer les limites de ressources (propriétés, locataires, etc.)
- ✅ Fournir les messages de blocage personnalisés
- ✅ Mapper les fonctionnalités aux icônes et labels

**Méthodes clés** :
```php
hasAccess(Organization $organization, string $feature): bool
userHasAccess(?User $user, string $feature): bool
getFeatureLabel(string $feature): string
getFeatureIcon(string $feature): string
getLimitInfo(Organization $organization, string $resourceType): array
getFeatureBlockMessage(string $feature, Organization $organization): string
getRequiredPlan(string $feature): string
```

---

### 2. **Extension Twig : `FeatureExtension`**

**Fichier** : `src/Twig/FeatureExtension.php`

**Fonctions Twig disponibles** :
```twig
{% if has_feature('online_payments') %}
    {# Afficher le bouton de paiement en ligne #}
{% endif %}

{{ feature_label('online_payments') }}  {# Paiements en ligne (CinetPay) #}
{{ feature_icon('online_payments') }}   {# bi-credit-card-2-front #}
{{ feature_block_message('online_payments') }}
{{ required_plan('online_payments') }}  {# professional #}
```

---

### 3. **Event Listener : `FeatureAccessListener`**

**Fichier** : `src/EventListener/FeatureAccessListener.php`

**Rôle** : Intercepte les requêtes HTTP et bloque l'accès aux routes protégées si l'utilisateur n'a pas la fonctionnalité requise.

**Routes protégées** :
- `app_accounting_*` → Nécessite `accounting`
- `app_maintenance_request_*` → Nécessite `maintenance_requests`
- `app_online_payment_*` → Nécessite `online_payments`
- `app_advance_payment_*` → Nécessite `advance_payments`
- `app_admin_orange_sms_*` → Nécessite `sms_notifications`
- `app_admin_branding` → Nécessite `custom_branding`
- `app_api_*` → Nécessite `api_access`

---

### 4. **Controller : `SubscriptionManagementController`**

**Fichier** : `src/Controller/SubscriptionManagementController.php`

**Routes disponibles** :
- `/mon-abonnement/` → Tableau de bord de l'abonnement
- `/mon-abonnement/upgrade` → Page d'amélioration de plan
- `/mon-abonnement/fonctionnalite-bloquee/{feature}` → Détails d'une fonctionnalité bloquée

---

## 📊 Fonctionnalités Disponibles par Plan

### **FREEMIUM** (Gratuit)
- ✅ `dashboard` - Tableau de bord personnalisé
- ✅ `properties_management` - Gestion des propriétés (max 2)
- ✅ `tenants_management` - Gestion des locataires (max 3)
- ✅ `lease_management` - Gestion des baux
- ✅ `payment_tracking` - Suivi des paiements

**Limites** :
- 2 propriétés
- 3 locataires
- 1 utilisateur
- 10 documents

---

### **STARTER** (9 900 FCFA/mois)
Toutes les features Freemium +
- ✅ `documents` - Gestion des documents (max 50)

**Limites** :
- 5 propriétés
- 10 locataires
- 2 utilisateurs
- 50 documents

---

### **PROFESSIONAL** (24 900 FCFA/mois) ⭐ PLUS POPULAIRE
Toutes les features Starter +
- ✅ `accounting` - Comptabilité avancée
- ✅ `maintenance_requests` - Demandes de maintenance
- ✅ `online_payments` - Paiements en ligne (CinetPay)
- ✅ `advance_payments` - Paiements anticipés (Acomptes)
- ✅ `reports` - Rapports et statistiques
- ✅ `email_notifications` - Notifications par email
- ✅ `rent_receipts` - Quittances automatiques
- ✅ `payment_notices` - Avis d'échéances automatiques
- ✅ `messaging` - Messagerie interne
- ✅ `tasks_automation` - Automatisation des tâches

**Limites** :
- 20 propriétés
- 50 locataires
- 5 utilisateurs
- 200 documents

---

### **ENTERPRISE** (49 900 FCFA/mois)
Toutes les features Professional +
- ✅ `sms_notifications` - Notifications par SMS (Orange API)
- ✅ `custom_branding` - Personnalisation (Logo, Couleurs)
- ✅ `api_access` - Accès API REST
- ✅ `priority_support` - Support prioritaire
- ✅ `multi_currency` - Multi-devises

**Limites** :
- ∞ propriétés illimitées
- ∞ locataires illimités
- ∞ utilisateurs illimités
- ∞ documents illimités

---

## 🎨 Templates Créés

### 1. **`templates/subscription/index.html.twig`**
Tableau de bord de l'abonnement affichant :
- Plan actuel et statut
- Dates de début/fin
- Période d'essai restante
- Utilisation des ressources (avec barres de progression)
- Liste des fonctionnalités disponibles

### 2. **`templates/subscription/upgrade.html.twig`**
Page de mise à niveau affichant :
- Tous les plans disponibles
- Comparaison avec le plan actuel
- Fonctionnalités clés de chaque plan
- Boutons d'action (Upgrade/Downgrade)

### 3. **`templates/subscription/blocked_feature.html.twig`**
Page affichée quand l'utilisateur tente d'accéder à une fonctionnalité non disponible :
- Icône de blocage
- Message d'explication personnalisé
- Plan actuel vs plan requis
- Liste des plans incluant la fonctionnalité
- Boutons pour upgrade

### 4. **`templates/registration/plans.html.twig`**
**Améliorations** :
- Affichage professionnel des fonctionnalités avec icônes
- Utilisation de `feature_label()` et `feature_icon()`
- Labels en français clairs et précis

### 5. **`templates/registration/register.html.twig`**
**Améliorations** :
- Liste dynamique des fonctionnalités du plan choisi
- Gestion spéciale du plan Freemium (pas de cycle de facturation)
- Affichage "GRATUIT Pour toujours"

---

## 🔐 Synchronisation Plan → Organisation

**Lors de l'inscription** (`RegistrationController::register`) :

```php
// Copier les fonctionnalités du plan vers l'organisation
$organization->setFeatures($plan->getFeatures());

// Définir les limites basées sur le plan
$organization->setSetting('max_properties', $plan->getMaxProperties());
$organization->setSetting('max_tenants', $plan->getMaxTenants());
$organization->setSetting('max_users', $plan->getMaxUsers());
$organization->setSetting('max_documents', $plan->getMaxDocuments());
```

Cela garantit que l'organisation hérite **exactement** des fonctionnalités et limites du plan choisi.

---

## 🛡️ Contrôle d'Accès

### **1. Blocage au niveau Route (FeatureAccessListener)**
```php
// Si l'utilisateur essaie d'accéder à une route protégée
// sans la fonctionnalité requise, il est redirigé avec un message
```

### **2. Blocage au niveau Template (Twig)**
```twig
{% if has_feature('accounting') %}
    <a href="{{ path('app_accounting_index') }}">Ma Comptabilité</a>
{% else %}
    <span class="text-muted" title="Non disponible dans votre plan">
        <i class="bi bi-lock"></i> Ma Comptabilité
    </span>
{% endif %}
```

### **3. Blocage au niveau Controller**
```php
if (!$this->featureAccessService->userHasAccess($user, 'online_payments')) {
    throw $this->createAccessDeniedException('Cette fonctionnalité nécessite un plan supérieur.');
}
```

---

## 📈 Gestion des Limites

### **Vérifier si une limite est atteinte** :
```php
$propertyLimit = $featureAccessService->getLimitInfo($organization, 'properties');

if ($propertyLimit['is_reached']) {
    // Bloquer la création d'une nouvelle propriété
    throw new \Exception("Limite de propriétés atteinte ({$propertyLimit['max']}). Passez à un plan supérieur.");
}
```

### **Afficher les limites dans un template** :
```twig
{% set limit = feature_limit_info('properties') %}
<div class="progress">
    <div class="progress-bar bg-{{ limit.percentage > 80 ? 'danger' : 'success' }}"
         style="width: {{ limit.percentage }}%">
        {{ limit.current }} / {{ limit.max ?? '∞' }}
    </div>
</div>
```

---

## 🎯 Menu Dynamique

Le menu "Mon Abonnement" a été ajouté dans `MenuService` :

```php
'subscription' => [
    'label' => 'Mon Abonnement',
    'icon' => 'bi-credit-card-2-back',
    'route' => 'app_subscription_dashboard',
    'roles' => ['ROLE_ADMIN'],
    'order' => 9.5,
],
```

Visible uniquement pour les administrateurs d'organisation.

---

## 🚀 Prochaines Étapes

### ✅ **Complété** :
1. ✅ Système de gestion des fonctionnalités
2. ✅ Contrôle d'accès par route
3. ✅ Templates professionnels
4. ✅ Synchronisation Plan → Organisation
5. ✅ Affichage des limites
6. ✅ Pages de gestion d'abonnement

### 🔄 **En attente** :
1. ⏳ Intégration paiement CinetPay pour les abonnements
2. ⏳ Page d'upgrade en temps réel (changement de plan)
3. ⏳ Historique des abonnements
4. ⏳ Facturation automatique
5. ⏳ Notifications d'expiration de période d'essai

---

## 🎓 Utilisation Pratique

### **Exemple 1 : Vérifier une fonctionnalité dans un controller**
```php
use App\Service\FeatureAccessService;

public function createPayment(FeatureAccessService $featureAccess): Response
{
    $user = $this->getUser();
    
    if (!$featureAccess->userHasAccess($user, 'online_payments')) {
        $this->addFlash('warning', $featureAccess->getFeatureBlockMessage('online_payments', $user->getOrganization()));
        return $this->redirectToRoute('app_subscription_upgrade');
    }
    
    // Continuer le traitement...
}
```

### **Exemple 2 : Afficher conditionnellement dans Twig**
```twig
{% if has_feature('sms_notifications') %}
    <button class="btn btn-primary" onclick="sendSms()">
        <i class="bi bi-phone"></i> Envoyer SMS
    </button>
{% else %}
    <a href="{{ path('app_subscription_upgrade') }}" class="btn btn-outline-secondary">
        <i class="bi bi-lock"></i> SMS (Plan Enterprise requis)
    </a>
{% endif %}
```

### **Exemple 3 : Vérifier les limites avant création**
```php
public function createProperty(FeatureAccessService $featureAccess): Response
{
    $user = $this->getUser();
    $organization = $user->getOrganization();
    
    if ($featureAccess->hasReachedLimit($organization, 'properties')) {
        $this->addFlash('danger', 'Vous avez atteint la limite de propriétés de votre plan. Passez à un plan supérieur.');
        return $this->redirectToRoute('app_subscription_upgrade');
    }
    
    // Créer la propriété...
}
```

---

## ✨ Résultat Final

**Le système garantit que** :
- ✅ Les fonctionnalités affichées = Fonctionnalités réellement disponibles
- ✅ L'accès est contrôlé à tous les niveaux (Route, Controller, Template)
- ✅ Les limites sont respectées et affichées en temps réel
- ✅ L'expérience utilisateur est professionnelle avec messages clairs
- ✅ L'upgrade est facile et bien mis en avant

**Un système de gestion d'abonnement SaaS professionnel et complet ! 🎉**

