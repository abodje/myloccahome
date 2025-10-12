# 🎛️ Guide - Gestion des menus via l'interface d'administration

## 📋 Vue d'ensemble

MYLOCCA dispose maintenant d'une **interface d'administration complète** pour gérer les menus et les permissions ACL directement depuis le navigateur web, sans modifier le code !

---

## ✨ NOUVELLES FONCTIONNALITÉS

### 1. Interface de gestion des menus

**URL** : `/admin/menus`

**Fonctionnalités** :
- ✅ Voir tous les menus configurés
- ✅ Créer de nouveaux menus
- ✅ Modifier les menus existants
- ✅ Activer/Désactiver des menus
- ✅ Supprimer des menus
- ✅ Synchroniser depuis le code

---

### 2. Synchronisation depuis le code

**Fonctionnalité** : Bouton "Synchroniser"

**Action** : Importe automatiquement tous les menus définis dans `MenuService.php` vers la base de données.

**Utilité** :
- Migration initiale
- Mise à jour après modifications du code
- Reset complet de la configuration

---

### 3. Gestion des permissions

**Par menu** : 
- Choisir les rôles autorisés (ROLE_USER, ROLE_TENANT, ROLE_MANAGER, ROLE_ADMIN)
- Modifier sans redémarrage de l'application
- Application immédiate

---

## 📦 CE QUI A ÉTÉ CRÉÉ

### 1. Entité MenuItem

**Fichier** : `src/Entity/MenuItem.php`

**Champs** :
- `label` : Texte affiché
- `menuKey` : Identifiant unique
- `icon` : Icône Bootstrap
- `route` : Route Symfony
- `roles` : Rôles autorisés (JSON)
- `displayOrder` : Ordre d'affichage
- `isActive` : Actif/Inactif
- `type` : menu, divider, submenu
- `parent` : Menu parent (pour sous-menus)
- `badgeType` : Type de badge
- `description` : Description

---

### 2. Repository

**Fichier** : `src/Repository/MenuItemRepository.php`

**Méthodes** :
- `findActiveMenus()` : Menus actifs uniquement
- `findByKey(string)` : Chercher par clé
- `findRootMenus()` : Menus principaux (sans parent)

---

### 3. Contrôleur

**Fichier** : `src/Controller/Admin/MenuController.php`

**Routes** :
- `GET /admin/menus` - Liste des menus
- `GET/POST /admin/menus/nouveau` - Créer un menu
- `GET/POST /admin/menus/{id}/modifier` - Modifier un menu
- `POST /admin/menus/{id}/toggle` - Activer/Désactiver
- `POST /admin/menus/{id}/supprimer` - Supprimer
- `POST /admin/menus/synchroniser` - Synchroniser depuis le code

---

### 4. Templates

**Fichiers** :
- `templates/admin/menu/index.html.twig` - Liste
- `templates/admin/menu/new.html.twig` - Création
- `templates/admin/menu/edit.html.twig` - Édition

---

### 5. Migration

**Fichier** : `migrations/Version20251012104514.php`

**Table créée** : `menu_item`

**Colonnes** :
- id, label, menu_key, icon, route, roles, display_order, is_active, type, parent_id, badge_type, description, created_at, updated_at

---

## 🚀 UTILISATION

### Première utilisation

#### 1. Accéder à l'interface

```
URL : http://localhost:8000/admin/menus
```

#### 2. Synchroniser les menus

Cliquer sur le bouton **"Synchroniser"**

**Résultat** : Tous les menus définis dans `MenuService.php` sont importés en base de données.

---

### Créer un nouveau menu

#### 1. Cliquer sur "Nouveau menu"

#### 2. Remplir le formulaire

**Champs obligatoires** :
- **Label** : "Mon nouveau menu"
- **Clé unique** : "mon_menu"
- **Rôles** : Cocher au moins un rôle

**Champs optionnels** :
- **Icône** : "bi-star"
- **Route** : "app_mon_menu"
- **Ordre** : 50
- **Type** : Menu normal / Séparateur
- **Parent** : Menu principal ou sous-menu

#### 3. Enregistrer

Le menu apparaît **immédiatement** dans la sidebar pour les utilisateurs ayant les rôles appropriés !

---

### Modifier un menu existant

#### 1. Cliquer sur l'icône ✏️ "Modifier"

#### 2. Changer les valeurs

- Modifier le label
- Changer les rôles autorisés
- Ajuster l'ordre d'affichage
- Changer l'icône

#### 3. Enregistrer

Les changements sont **appliqués immédiatement** !

---

### Activer/Désactiver un menu

**Action** : Cliquer sur le badge "Actif" / "Inactif"

**Effet** :
- **Inactif** : Le menu disparaît de la sidebar
- **Actif** : Le menu réapparaît

**Utilité** : Masquer temporairement un menu sans le supprimer.

---

### Supprimer un menu

**Action** : Cliquer sur l'icône 🗑️ "Supprimer"

**Confirmation** : Dialog de confirmation

**Effet** : Le menu est **supprimé définitivement** de la base de données.

---

## 📊 EXEMPLES PRATIQUES

### Exemple 1 : Ajouter un menu "Statistiques"

```
Label : Statistiques
Clé : statistics
Icône : bi-bar-chart
Route : app_statistics_index
Rôles : ☑ ROLE_MANAGER, ☑ ROLE_ADMIN
Ordre : 90
Type : Menu normal
```

**Résultat** : Menu "Statistiques" visible pour Manager et Admin uniquement.

---

### Exemple 2 : Créer un sous-menu

```
Label : Statistiques financières
Clé : stats_financial
Icône : (vide)
Route : app_statistics_financial
Rôles : ☑ ROLE_ADMIN
Parent : Statistiques
```

**Résultat** : Sous-menu "Statistiques financières" sous le menu "Statistiques", visible uniquement pour Admin.

---

### Exemple 3 : Ajouter un séparateur

```
Label : OUTILS
Clé : divider_tools
Type : Séparateur
Rôles : ☑ ROLE_MANAGER, ☑ ROLE_ADMIN
Ordre : 200
```

**Résultat** : Ligne de séparation avec texte "OUTILS" dans la sidebar.

---

## 🎯 WORKFLOW COMPLET

### Scénario : Ajouter un nouveau module

#### Étape 1 : Créer la route et le contrôleur

```php
#[Route('/statistiques', name: 'app_statistics_index')]
public function index(): Response
{
    return $this->render('statistics/index.html.twig');
}
```

#### Étape 2 : Ajouter le menu via l'interface

1. Aller sur `/admin/menus`
2. Cliquer "Nouveau menu"
3. Remplir :
   - Label : "Statistiques"
   - Clé : "statistics"
   - Icône : "bi-bar-chart"
   - Route : "app_statistics_index"
   - Rôles : ROLE_MANAGER, ROLE_ADMIN
4. Enregistrer

#### Étape 3 : Vérifier

Se connecter avec un compte Manager → Le menu "Statistiques" apparaît !

---

## 🔍 FONCTIONNALITÉS AVANCÉES

### Synchronisation sélective

Si vous modifiez un menu via l'interface mais voulez le reset :

1. Modifier `MenuService.php`
2. Cliquer "Synchroniser"
3. Les menus existants en BDD sont **mis à jour** avec les valeurs du code

---

### Gestion des sous-menus

**Créer une hiérarchie** :

```
Paramètres (parent)
  ├── Application (sous-menu)
  ├── Devises (sous-menu)
  └── Email (sous-menu)
```

**Dans l'interface** :
1. Créer "Paramètres" (sans parent)
2. Créer "Application" avec Parent = "Paramètres"
3. Créer "Devises" avec Parent = "Paramètres"
4. etc.

---

### Badges de notification

**Configurer un badge** :

```
Menu : Mes demandes
Badge Type : pending_requests
```

**Implémenter le comptage** (dans `MenuService.php`) :

```php
public function getPendingRequestsCount(): int
{
    return $this->maintenanceRepository->count(['status' => 'Nouvelle']);
}
```

---

## 🎨 PERSONNALISATION

### Icônes disponibles

Utilisez [Bootstrap Icons](https://icons.getbootstrap.com/) :

```
bi-speedometer2     📊 Dashboard
bi-house            🏠 Propriétés
bi-people           👥 Utilisateurs
bi-gear             ⚙️ Paramètres
bi-bar-chart        📈 Statistiques
bi-envelope         📧 Emails
bi-calendar         📅 Calendrier
bi-file-text        📄 Documents
```

---

### Types de menus

| Type | Description | Usage |
|------|-------------|-------|
| `menu` | Menu normal | Par défaut |
| `divider` | Séparateur | Organiser visuellement |
| `submenu` | Sous-menu | Hiérarchie (géré via parent) |

---

## 🛡️ SÉCURITÉ

### Permissions

- ✅ Seuls les **ROLE_ADMIN** peuvent accéder à `/admin/menus`
- ✅ Les modifications sont appliquées **en temps réel**
- ✅ Pas de redémarrage nécessaire
- ✅ Historique des modifications (created_at, updated_at)

---

### Validation

- ✅ Clé unique obligatoire (pas de doublons)
- ✅ Au moins un rôle requis
- ✅ Routes Symfony valides recommandées

---

## 🐛 DÉPANNAGE

### Le menu n'apparaît pas

**Vérifications** :
1. Menu actif ? (statut = Actif)
2. Rôle correct ? (vérifier les rôles cochés)
3. Route valide ? (`php bin/console debug:router`)
4. Cache ? (`php bin/console cache:clear`)

---

### Erreur "Route does not exist"

**Solution** :
1. Vérifier que la route existe dans un contrôleur
2. Vérifier l'orthographe exacte
3. Ou laisser le champ "Route" vide pour les dividers

---

### Sous-menu ne s'affiche pas

**Vérifications** :
1. Parent correctement défini ?
2. Parent est actif ?
3. Rôles du parent et sous-menu compatibles ?

---

## 📈 STATISTIQUES

### Table menu_item

**Colonnes** : 14
**Relations** : 1 (self-referencing pour parent/children)
**Index** : Sur menu_key (unique)

---

## ✅ CHECKLIST PRODUCTION

- [ ] Migration exécutée
- [ ] Menus synchronisés depuis le code
- [ ] Tests effectués avec différents rôles
- [ ] Icônes vérifiées
- [ ] Routes validées
- [ ] Documentation lue par l'équipe
- [ ] Formation des admins effectuée

---

## 🎊 AVANTAGES

### 1. Flexibilité

✅ Modifier les menus sans toucher au code  
✅ Activation/désactivation instantanée  
✅ Tests faciles (toggle on/off)  

### 2. Maintenabilité

✅ Interface intuitive  
✅ Pas besoin d'être développeur  
✅ Historique des modifications  

### 3. Performance

✅ Menus stockés en BDD (rapide)  
✅ Pas de recompilation nécessaire  
✅ Cache possible  

### 4. Sécurité

✅ Gestion centralisée des permissions  
✅ Audit trail (created_at, updated_at)  
✅ Accès réservé aux admins  

---

## 🚀 PROCHAINES ÉTAPES POSSIBLES

### Améliorations futures

1. **Drag & Drop** : Réorganiser les menus par glisser-déposer
2. **Import/Export** : Exporter la configuration en JSON
3. **Historique** : Log complet des modifications
4. **Preview** : Prévisualiser le menu avant de sauvegarder
5. **Traductions** : Support multilingue des labels

---

## 📚 DOCUMENTATION LIÉE

- `ACL_SYSTEM_GUIDE.md` - Système ACL de base
- `RECAPITULATIF_ACL.md` - Récapitulatif ACL

---

**📅 Version** : 1.0  
**📄 Date** : 12 Octobre 2025  
**✨ Statut** : Opérationnel  

---

**🎛️ Vous pouvez maintenant gérer les menus via l'interface d'administration !**

