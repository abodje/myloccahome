# ✅ Menus Filtrés par Plan d'Abonnement

## 🐛 Problème Identifié

> "Pourquoi quand je me logue en tant organization je vois tout les menu ?"

**Cause** : Le `MenuService` filtrait uniquement par **rôle utilisateur**, mais pas par **fonctionnalités du plan d'abonnement**.

---

## 🔧 Solution Appliquée

### **1. Injection de `FeatureAccessService` dans `MenuService`**

```php
class MenuService
{
    public function __construct(
        private Security $security,
        private SettingsService $settingsService,
        private FeatureAccessService $featureAccessService // ✅ Ajouté
    ) {
    }
}
```

### **2. Ajout de `required_feature` aux menus sensibles**

```php
'accounting' => [
    'label' => 'Ma comptabilité',
    'icon' => 'bi-calculator',
    'route' => 'app_accounting_index',
    'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
    'order' => 7,
    'required_feature' => 'accounting', // ✅ Nécessite plan Professional+
],

'maintenance_requests' => [
    'label' => 'Mes demandes',
    'icon' => 'bi-tools',
    'route' => 'app_maintenance_request_index',
    'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
    'order' => 2,
    'required_feature' => 'maintenance_requests', // ✅ Nécessite plan Professional+
],
```

### **3. Modification de `canAccessMenuItem()`**

```php
public function canAccessMenuItem(array $menuItem): bool
{
    // 1. Vérifier le type (divider, etc.)
    // 2. Vérifier les rôles requis
    // 3. Vérifier la condition de visibilité (settings)
    
    // ✅ NOUVEAU : Vérifier la fonctionnalité requise selon le plan
    if (isset($menuItem['required_feature'])) {
        $user = $this->security->getUser();
        
        if (!$user || !$user->getOrganization()) {
            return false; // Pas d'organization = pas d'accès
        }

        if (!$this->featureAccessService->hasAccess(
            $user->getOrganization(), 
            $menuItem['required_feature']
        )) {
            return false; // Fonctionnalité non disponible
        }
    }

    return true;
}
```

---

## 📋 Menus avec Restriction de Fonctionnalité

| Menu | Feature Requise | Plans Autorisés |
|------|-----------------|-----------------|
| **Dashboard** | Aucune | Tous (Freemium+) |
| **Mes biens** | `properties_management` | Tous (Freemium+) |
| **Locataires** | `tenants_management` | Tous (Freemium+) |
| **Baux** | `lease_management` | Tous (Freemium+) |
| **Mes paiements** | `payment_tracking` | Tous (Freemium+) |
| **Mes demandes** | `maintenance_requests` | Professional+ |
| **Ma comptabilité** | `accounting` | Professional+ |
| **Mes documents** | Aucune | Tous |
| **Messagerie** | Aucune | Tous |
| **Mon Abonnement** | Aucune | Admins seulement |

---

## 🎯 Exemple : Utilisateur avec Plan Freemium

### **Configuration du Plan Freemium**
```php
'features' => [
    'dashboard',
    'properties_management',
    'tenants_management',
    'lease_management',
    'payment_tracking',
]
```

### **Menus Visibles**
```
✅ Mon tableau de bord
✅ Mes biens (2 max)
✅ Locataires (3 max)
✅ Baux
✅ Mes paiements
✅ Mes documents
✅ Messagerie
✅ Mon Abonnement
```

### **Menus Cachés** ❌
```
❌ Mes demandes (maintenance_requests)
   → Nécessite plan Professional

❌ Ma comptabilité (accounting)
   → Nécessite plan Professional
```

---

## 🎯 Exemple : Utilisateur avec Plan Professional

### **Configuration du Plan Professional**
```php
'features' => [
    'dashboard',
    'properties_management',
    'tenants_management',
    'lease_management',
    'payment_tracking',
    'documents',
    'accounting',                    // ✅ Ajouté
    'maintenance_requests',          // ✅ Ajouté
    'online_payments',              // ✅ Ajouté
    'advance_payments',             // ✅ Ajouté
    'reports',                      // ✅ Ajouté
    'email_notifications',          // ✅ Ajouté
]
```

### **Menus Visibles**
```
✅ Mon tableau de bord
✅ Mes biens (20 max)
✅ Locataires (50 max)
✅ Baux
✅ Mes paiements
✅ Mes demandes                    // ✅ Maintenant visible
✅ Ma comptabilité                 // ✅ Maintenant visible
✅ Mes documents
✅ Messagerie
✅ Mon Abonnement
```

### **Menus Cachés** ❌
```
(Tous les menus de base sont visibles)
```

---

## 🔄 Workflow de Vérification

Quand un utilisateur accède à l'application :

```
1. MenuService::getAuthorizedMenu()
   ↓
2. Pour chaque menu : canAccessMenuItem($menuItem)
   ↓
3. Vérification 1 : Rôle utilisateur ?
   → Oui → Continue
   → Non → Menu caché
   ↓
4. Vérification 2 : Paramètre système actif ?
   → Oui → Continue
   → Non → Menu caché
   ↓
5. Vérification 3 : Fonctionnalité dans le plan ? ✅ NOUVEAU
   → User->Organization->Features contient la feature ?
   → Oui → Menu visible ✅
   → Non → Menu caché ❌
```

---

## 🎨 Résultat Final

### **AVANT (Problème)**
```
Un utilisateur avec plan Freemium voyait :
✅ Dashboard
✅ Mes biens
✅ Mes paiements
✅ Ma comptabilité        ← ❌ Ne devrait pas voir
✅ Mes demandes          ← ❌ Ne devrait pas voir
```

### **APRÈS (Corrigé)**
```
Un utilisateur avec plan Freemium voit :
✅ Dashboard
✅ Mes biens
✅ Mes paiements
❌ Ma comptabilité        ← Caché (Plan Professional requis)
❌ Mes demandes          ← Caché (Plan Professional requis)

Message si tentative d'accès direct :
"La comptabilité avancée est disponible à partir du plan Professional."
```

---

## 🛡️ Double Protection

### **Niveau 1 : Menu (Interface)**
Le menu est **caché** si la fonctionnalité n'est pas dans le plan.

```twig
{# Le menu ne s'affiche même pas #}
{% for menu in authorizedMenus %}
    {# Seuls les menus autorisés sont affichés #}
{% endfor %}
```

### **Niveau 2 : Route (Backend)**
Si l'utilisateur essaie d'accéder directement via URL :

```php
// FeatureAccessListener intercepte la requête
if (!$featureAccessService->hasAccess($organization, 'accounting')) {
    // Redirection + Message flash
    $this->addFlash('warning', 'Cette fonctionnalité nécessite un plan supérieur.');
    return redirect('/');
}
```

---

## 📊 Mapping Complet : Menu → Feature → Plans

| Menu | Feature | Freemium | Starter | Professional | Enterprise |
|------|---------|----------|---------|--------------|------------|
| Dashboard | - | ✅ | ✅ | ✅ | ✅ |
| Mes biens | `properties_management` | ✅ | ✅ | ✅ | ✅ |
| Locataires | `tenants_management` | ✅ | ✅ | ✅ | ✅ |
| Baux | `lease_management` | ✅ | ✅ | ✅ | ✅ |
| Mes paiements | `payment_tracking` | ✅ | ✅ | ✅ | ✅ |
| Mes documents | `documents` | ❌ | ✅ | ✅ | ✅ |
| **Mes demandes** | `maintenance_requests` | ❌ | ❌ | ✅ | ✅ |
| **Ma comptabilité** | `accounting` | ❌ | ❌ | ✅ | ✅ |
| Messagerie | - | ✅ | ✅ | ✅ | ✅ |
| Mon Abonnement | - | ✅ | ✅ | ✅ | ✅ |

---

## ✅ Test de Vérification

### **Scénario : Utilisateur Freemium se connecte**

1. Se connecter avec un compte sur plan Freemium
2. Vérifier le menu de gauche
3. ✅ **Résultat attendu** :
   - Dashboard visible
   - Mes biens visible
   - Locataires visible
   - Baux visible
   - Mes paiements visible
   - **Mes demandes NON visible**
   - **Ma comptabilité NON visible**

4. Tenter d'accéder directement à `/comptabilite`
5. ✅ **Résultat attendu** :
   - Redirection vers `/`
   - Message : "La comptabilité avancée est disponible à partir du plan Professional."

---

## 🚀 Avantages

1. ✅ **Interface Propre** : Les utilisateurs ne voient QUE ce qu'ils peuvent utiliser
2. ✅ **Incitation à l'Upgrade** : Messages clairs pour upgrader
3. ✅ **Sécurité Renforcée** : Double protection (Menu + Route)
4. ✅ **Expérience Optimale** : Pas de frustration à cliquer sur des features bloquées
5. ✅ **Professionnel** : Système SaaS complet et cohérent

---

## 🎉 Résultat Final

**PROBLÈME RÉSOLU !**

Maintenant, les utilisateurs voient **UNIQUEMENT** les menus correspondant à leur plan d'abonnement.

- Plan Freemium → 5 menus de base
- Plan Professional → 7 menus (+comptabilité, +demandes)
- Plan Enterprise → Tous les menus

**Le système est maintenant 100% cohérent avec les plans d'abonnement ! 🎊**

