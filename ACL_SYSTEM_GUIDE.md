# 🔐 Guide - Système ACL (Access Control List)

## 📋 Vue d'ensemble

MYLOCCA dispose maintenant d'un **système ACL complet** qui gère automatiquement l'affichage des menus et l'accès aux fonctionnalités selon les rôles des utilisateurs.

---

## 🎯 FONCTIONNALITÉS

### 1. Gestion automatique des menus

✅ Les menus s'affichent automatiquement selon le rôle de l'utilisateur  
✅ Configuration centralisée dans un seul fichier  
✅ Sous-menus avec permissions indépendantes  
✅ Dividers (séparateurs) pour organiser les menus  
✅ Badges dynamiques pour notifications  

### 2. Vérification d'accès aux routes

✅ Fonction Twig `can_access_route()` pour vérifier les permissions  
✅ Protection automatique basée sur la configuration  
✅ Gestion des sous-menus avec permissions granulaires  

### 3. Information sur les permissions

✅ Fonction `user_permissions()` retournant toutes les permissions  
✅ Accès facile dans les templates Twig  

---

## 📦 ARCHITECTURE

### Fichiers créés

```
src/
├── Service/
│   └── MenuService.php          # Service principal ACL
└── Twig/
    └── MenuExtension.php        # Extension Twig

templates/
└── _partials/
    └── sidebar.html.twig         # Template sidebar avec ACL
```

---

## 🎨 CONFIGURATION DES MENUS

### Structure dans `MenuService.php`

```php
'menu_key' => [
    'label' => 'Libellé du menu',
    'icon' => 'bi-icon-name',      // Icône Bootstrap Icons
    'route' => 'route_name',        // Nom de la route Symfony
    'roles' => ['ROLE_...'],        // Rôles autorisés
    'order' => 1,                   // Ordre d'affichage
    'badge' => 'badge_type',        // (Optionnel) Badge de notification
    'submenu' => [...]              // (Optionnel) Sous-menu
]
```

### Exemple complet

```php
'properties' => [
    'label' => 'Mes biens',
    'icon' => 'bi-building',
    'route' => 'app_property_index',
    'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
    'order' => 3,
],
```

---

## 👥 RÔLES DISPONIBLES

| Rôle | Description | Accès |
|------|-------------|-------|
| `ROLE_USER` | Utilisateur de base | Dashboard, Profil |
| `ROLE_TENANT` | Locataire | Dashboard, Demandes, Paiements, Documents |
| `ROLE_MANAGER` | Gestionnaire | + Propriétés, Locataires, Baux, Comptabilité |
| `ROLE_ADMIN` | Administrateur | Accès complet + Administration |

### Hiérarchie des rôles

```
ROLE_USER
    └── ROLE_TENANT
            └── ROLE_MANAGER
                    └── ROLE_ADMIN
```

---

## 🔧 UTILISATION

### Dans les templates Twig

#### 1. Afficher le menu autorisé

```twig
{% set menu_items = get_menu() %}

{% for key, item in menu_items %}
    <a href="{{ path(item.route) }}">
        <i class="{{ item.icon }}"></i>
        {{ item.label }}
    </a>
{% endfor %}
```

#### 2. Vérifier l'accès à une route

```twig
{% if can_access_route('app_admin_users') %}
    <a href="{{ path('app_admin_users') }}">Gérer les utilisateurs</a>
{% endif %}
```

#### 3. Obtenir les permissions de l'utilisateur

```twig
{% set permissions = user_permissions() %}

{% if permissions.is_admin %}
    <div class="admin-panel">...</div>
{% endif %}
```

### Dans les contrôleurs PHP

```php
use App\Service\MenuService;

class MyController extends AbstractController
{
    public function __construct(
        private MenuService $menuService
    ) {}

    public function index(): Response
    {
        // Obtenir le menu autorisé
        $menu = $this->menuService->getAuthorizedMenu();
        
        // Vérifier l'accès à une route
        if ($this->menuService->canAccessRoute('app_admin_users')) {
            // Autoriser l'action
        }
        
        // Obtenir les permissions
        $permissions = $this->menuService->getUserPermissions();
        
        return $this->render('page.html.twig', [
            'menu' => $menu,
            'permissions' => $permissions,
        ]);
    }
}
```

---

## 📝 AJOUTER UN NOUVEAU MENU

### Étape 1 : Ajouter dans `MenuService.php`

```php
'mon_menu' => [
    'label' => 'Mon nouveau menu',
    'icon' => 'bi-star',
    'route' => 'app_mon_menu',
    'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
    'order' => 50,
],
```

### Étape 2 : Le menu apparaît automatiquement !

Le système ACL affiche automatiquement le menu pour les utilisateurs ayant les rôles requis.

---

## 🎛️ MENU AVEC SOUS-MENUS

### Configuration

```php
'admin_settings' => [
    'label' => 'Paramètres',
    'icon' => 'bi-sliders',
    'route' => 'app_admin_settings',
    'roles' => ['ROLE_ADMIN'],
    'order' => 105,
    'submenu' => [
        'settings_app' => [
            'label' => 'Application',
            'route' => 'app_admin_settings_application',
            'roles' => ['ROLE_ADMIN'],
        ],
        'settings_email' => [
            'label' => 'Email',
            'route' => 'app_admin_settings_email',
            'roles' => ['ROLE_ADMIN'],
        ],
    ],
],
```

### Rendu dans le template

Le sous-menu apparaît avec un bouton de déroulement (collapse) automatiquement.

---

## 🏷️ DIVIDERS (SÉPARATEURS)

### Configuration

```php
'divider_admin' => [
    'type' => 'divider',
    'label' => 'ADMINISTRATION',
    'roles' => ['ROLE_ADMIN'],
    'order' => 100,
],
```

### Rendu

Affiche un séparateur avec label en majuscules pour organiser visuellement le menu.

---

## 🔔 BADGES DE NOTIFICATION

### Configuration

```php
'maintenance_requests' => [
    'label' => 'Mes demandes',
    'icon' => 'bi-tools',
    'route' => 'app_maintenance_request_index',
    'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'],
    'badge' => 'pending_requests',  // Type de badge
],
```

### Implémentation

Dans `MenuService.php`, ajoutez la méthode de comptage :

```php
public function getPendingRequestsCount(): int
{
    return $this->maintenanceRepository->count([
        'status' => 'Nouvelle'
    ]);
}
```

Dans le template `sidebar.html.twig` :

```twig
{% if item.badge|default('') == 'pending_requests' %}
    {% set pending_count = menu_service.getPendingRequestsCount() %}
    {% if pending_count > 0 %}
        <span class="badge bg-danger ms-auto">{{ pending_count }}</span>
    {% endif %}
{% endif %}
```

---

## 🎨 PERSONNALISATION VISUELLE

### Modifier les icônes

Utilisez les [Bootstrap Icons](https://icons.getbootstrap.com/) :

```php
'icon' => 'bi-house',      // Maison
'icon' => 'bi-people',     // Personnes
'icon' => 'bi-gear',       // Paramètres
'icon' => 'bi-graph-up',   // Graphique
```

### Modifier les couleurs

Dans `templates/_partials/sidebar.html.twig` :

```css
.sidebar {
    background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
}

.sidebar .nav-link.active {
    background-color: var(--primary-color, #5a8fb3);
}
```

---

## 🔍 DEBUGGING

### Voir les menus disponibles

```twig
{{ dump(get_menu()) }}
```

### Voir les permissions de l'utilisateur

```twig
{{ dump(user_permissions()) }}
```

### Vérifier un rôle spécifique

```twig
{% if is_granted('ROLE_ADMIN') %}
    Vous êtes admin
{% endif %}
```

---

## 🧪 TESTS

### Tester un rôle TENANT

1. Créer un utilisateur avec rôle `ROLE_TENANT`
2. Se connecter
3. Vérifier que seuls ces menus s'affichent :
   - Mon tableau de bord
   - Mes demandes
   - Mes paiements
   - Mes documents

### Tester un rôle MANAGER

1. Créer un utilisateur avec rôle `ROLE_MANAGER`
2. Se connecter
3. Vérifier les menus supplémentaires :
   - Mes biens
   - Locataires
   - Baux
   - Ma comptabilité

### Tester un rôle ADMIN

1. Se connecter en tant qu'admin
2. Vérifier tous les menus incluant :
   - Section ADMINISTRATION
   - Admin Dashboard
   - Utilisateurs
   - Tâches automatisées
   - Templates emails
   - Paramètres

---

## 📊 EXEMPLE COMPLET

### Scénario : Ajouter un menu "Statistiques"

#### 1. Ajouter dans `MenuService.php`

```php
'statistics' => [
    'label' => 'Statistiques',
    'icon' => 'bi-bar-chart',
    'route' => 'app_statistics_index',
    'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
    'order' => 90,
    'submenu' => [
        'stats_properties' => [
            'label' => 'Propriétés',
            'route' => 'app_statistics_properties',
            'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
        ],
        'stats_tenants' => [
            'label' => 'Locataires',
            'route' => 'app_statistics_tenants',
            'roles' => ['ROLE_MANAGER', 'ROLE_ADMIN'],
        ],
        'stats_financial' => [
            'label' => 'Financier',
            'route' => 'app_statistics_financial',
            'roles' => ['ROLE_ADMIN'], // Uniquement admin
        ],
    ],
],
```

#### 2. Résultat

- **Manager** verra : Propriétés, Locataires
- **Admin** verra : Propriétés, Locataires, Financier

---

## ⚙️ CONFIGURATION AVANCÉE

### Permissions dynamiques

Vous pouvez ajouter des vérifications personnalisées :

```php
public function canAccessMenuItem(array $menuItem): bool
{
    // Vérification des rôles standard
    if (!$this->hasAnyRole($menuItem['roles'] ?? [])) {
        return false;
    }
    
    // Vérifications personnalisées
    if (($menuItem['key'] ?? null) === 'accounting') {
        // Vérifier si l'utilisateur a accès à la comptabilité
        return $this->accountingService->userHasAccess($this->security->getUser());
    }
    
    return true;
}
```

### Cache des menus

Pour améliorer les performances :

```php
use Symfony\Contracts\Cache\CacheInterface;

public function getAuthorizedMenu(): array
{
    $userId = $this->security->getUser()?->getId();
    
    return $this->cache->get("menu_user_{$userId}", function() {
        // Calcul du menu
        return $this->calculateMenu();
    });
}
```

---

## 🚀 BONNES PRATIQUES

### 1. Organisation des menus

- Utilisez l'attribut `order` pour maintenir une cohérence
- Groupez les menus liés par ordre (10, 11, 12...)
- Séparez les sections avec des dividers

### 2. Nommage des clés

- Utilisez des noms descriptifs : `admin_settings`, `tenant_documents`
- Préfixez les menus admin avec `admin_`
- Soyez cohérent dans la convention

### 3. Rôles

- Attribuez le minimum de rôles nécessaires
- Utilisez la hiérarchie des rôles
- Ne dupliquez pas les permissions

### 4. Performance

- Limitez les appels au menu dans les templates
- Utilisez une variable pour stocker le résultat
- Considérez le cache pour les gros systèmes

---

## 🐛 DÉPANNAGE

### Menu ne s'affiche pas

1. Vérifier les rôles de l'utilisateur : `{{ dump(app.user.roles) }}`
2. Vérifier la configuration du menu dans `MenuService.php`
3. Vider le cache : `php bin/console cache:clear`

### Route introuvable

1. Vérifier que la route existe : `php bin/console debug:router`
2. Vérifier le nom de la route dans la configuration
3. Vérifier les imports de contrôleurs

### Badge ne s'affiche pas

1. Implémenter la méthode de comptage
2. Vérifier le type de badge dans la configuration
3. Vérifier le template sidebar.html.twig

---

## 📚 RÉFÉRENCES

### Symfony Security

- [Documentation officielle](https://symfony.com/doc/current/security.html)
- [Voters et Permissions avancées](https://symfony.com/doc/current/security/voters.html)

### Bootstrap Icons

- [Liste complète des icônes](https://icons.getbootstrap.com/)

---

## ✅ CHECKLIST DE MISE EN PRODUCTION

- [ ] Tous les menus configurés avec les bons rôles
- [ ] Routes existantes et accessibles
- [ ] Tests effectués pour chaque rôle
- [ ] Badges de notification implémentés
- [ ] Styles personnalisés appliqués
- [ ] Cache configuré (si nécessaire)
- [ ] Documentation à jour
- [ ] Formation des utilisateurs effectuée

---

## 🎊 RÉSULTAT

**Vous disposez maintenant d'un système ACL professionnel, flexible et facile à maintenir !**

### Avantages

✅ **Centralisé** : Une seule source de vérité  
✅ **Maintenable** : Ajout de menus en quelques lignes  
✅ **Sécurisé** : Permissions appliquées automatiquement  
✅ **Flexible** : Sous-menus, badges, dividers  
✅ **Performant** : Calcul optimisé des permissions  

---

**📅 Version** : 1.0  
**📄 Date** : 12 Octobre 2025  
**✨ Statut** : Opérationnel

---

**🔐 Votre système ACL est maintenant pleinement fonctionnel !**

