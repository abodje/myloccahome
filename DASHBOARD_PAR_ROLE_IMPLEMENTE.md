# 🎯 Dashboard Personnalisé par Rôle - Documentation

## 📋 Vue d'ensemble

Le système de tableau de bord a été entièrement restructuré pour afficher des informations personnalisées selon le rôle de l'utilisateur connecté. Chaque type d'utilisateur (Admin, Gestionnaire, Locataire) dispose désormais d'un dashboard adapté à ses besoins spécifiques.

---

## 🎨 Architecture du Système

### **Structure du Contrôleur**

Le `DashboardController` a été refactorisé avec une architecture modulaire :

```php
src/Controller/DashboardController.php
├── index()                    // Point d'entrée principal
├── adminDashboard()          // Dashboard Administrateur
├── managerDashboard()        // Dashboard Gestionnaire  
├── tenantDashboard()         // Dashboard Locataire
└── defaultDashboard()        // Dashboard par défaut
```

### **Templates Spécialisés**

Trois templates dédiés ont été créés :

```
templates/dashboard/
├── admin.html.twig           // Vue Administrateur
├── manager.html.twig         // Vue Gestionnaire
└── tenant.html.twig          // Vue Locataire
```

---

## 👨‍💼 Dashboard Administrateur

### **Fonctionnalités**

Le dashboard admin offre une vue d'ensemble complète de l'ensemble du système :

#### **Statistiques Globales**
- **Propriétés** : Total, occupées, libres
- **Locataires** : Total, actifs
- **Baux** : Actifs, expirant bientôt
- **Messages** : Non lus, total des conversations

#### **Statistiques Financières**
- **Revenus du Mois** : Total des paiements reçus
- **Dépenses du Mois** : Total des dépenses
- **Bénéfice Net** : Revenus - Dépenses
- **Paiements en Attente** : Total, en retard

#### **Alertes Urgentes**
- Demandes de maintenance urgentes
- Paiements en retard

#### **Activités Récentes**
- 5 derniers paiements
- 5 dernières demandes de maintenance

#### **Actions Rapides**
- Nouvelle Propriété
- Nouveau Locataire
- Nouveau Bail
- Paramètres

### **Route d'Accès**
```
URL: /
Condition: ROLE_ADMIN
Template: dashboard/admin.html.twig
```

---

## 🏢 Dashboard Gestionnaire

### **Fonctionnalités**

Le dashboard gestionnaire affiche uniquement les données liées aux propriétés qu'il gère :

#### **Statistiques Personnelles**
- **Mes Propriétés** : Total, occupées, libres
- **Mes Locataires** : Total, actifs
- **Mes Baux** : Actifs, expirant bientôt
- **Messages** : Non lus, total

#### **Statistiques Financières**
- **Revenus du Mois** : Revenus de ses propriétés
- **Paiements en Attente** : Paiements de ses locataires
- **Demandes de Maintenance** : Demandes sur ses propriétés

#### **Alertes Urgentes**
- Demandes de maintenance urgentes (ses propriétés)
- Paiements en retard (ses locataires)

#### **Activités Récentes**
- 5 derniers paiements (ses propriétés)
- 5 dernières demandes de maintenance (ses propriétés)

#### **Actions Rapides**
- Nouvelle Propriété
- Nouveau Locataire
- Nouveau Bail
- Nouveau Message

### **Route d'Accès**
```
URL: /
Condition: ROLE_MANAGER
Template: dashboard/manager.html.twig
```

### **Filtrage des Données**

Toutes les données sont filtrées par l'`Owner` associé à l'utilisateur gestionnaire :
- `$owner = $user->getOwner()`
- Utilisation de méthodes spécialisées dans les repositories

---

## 🏠 Dashboard Locataire

### **Fonctionnalités**

Le dashboard locataire affiche uniquement ses propres informations :

#### **Statistiques Personnelles**
- **Mes Propriétés** : Propriétés louées
- **Mes Baux Actifs** : Baux actifs, expirant bientôt
- **Mes Paiements** : En attente, en retard
- **Messages** : Non lus, total

#### **Statistiques Comptables**
- **Solde Comptable** : Solde du compte
- **Crédits du Mois** : Paiements effectués
- **Débits du Mois** : Loyers dus

#### **Mes Demandes**
- Demandes de maintenance en attente
- Demandes urgentes

#### **Alertes Importantes**
- Paiements en retard avec bouton "Payer maintenant"

#### **Activités Récentes**
- 5 derniers paiements
- 5 dernières demandes de maintenance

#### **Actions Rapides**
- Mes Propriétés
- Mes Paiements
- Nouvelle Demande
- Contacter

### **Route d'Accès**
```
URL: /
Condition: ROLE_TENANT
Template: dashboard/tenant.html.twig
```

### **Filtrage des Données**

Toutes les données sont filtrées par le `Tenant` associé à l'utilisateur :
- `$tenant = $user->getTenant()`
- Utilisation de méthodes spécialisées dans les repositories

---

## 🔧 Méthodes de Repository Ajoutées

### **PaymentRepository**

```php
// Paiements en retard pour un gestionnaire
findOverdueByManager(int $ownerId): array

// Paiements en retard pour un locataire  
findOverdueByTenant(int $tenantId): array

// Revenu mensuel pour un gestionnaire
getMonthlyIncomeByManager(int $ownerId): float
```

### **MaintenanceRequestRepository**

```php
// Demandes urgentes pour un gestionnaire
findUrgentPendingByManager(int $ownerId): array

// Demandes en retard pour un gestionnaire
findOverdueByManager(int $ownerId): array

// Demandes urgentes pour un locataire
findUrgentPendingByTenant(int $tenantId): array

// Demandes en retard pour un locataire
findOverdueByTenant(int $tenantId): array
```

### **LeaseRepository**

```php
// Baux expirant bientôt pour un gestionnaire
findExpiringSoonByManager(int $ownerId): array

// Baux expirant bientôt pour un locataire
findExpiringSoonByTenant(int $tenantId): array
```

---

## 🎨 Design et Interface

### **Cartes de Statistiques**

Chaque statistique est affichée dans une carte Bootstrap avec :
- **Icône** : Représentation visuelle
- **Valeur** : Chiffre principal
- **Détail** : Information complémentaire
- **Couleur** : Code couleur selon le contexte

### **Système de Couleurs**

```css
.border-left-primary   → Propriétés (bleu)
.border-left-success   → Revenus, locataires actifs (vert)
.border-left-info      → Baux, informations (cyan)
.border-left-warning   → Paiements en attente (orange)
.border-left-danger    → Dépenses, retards (rouge)
```

### **Badges de Statut**

```twig
{% if payment.status == 'Payé' %}
    <span class="badge badge-success">Payé</span>
{% else %}
    <span class="badge badge-warning">En attente</span>
{% endif %}
```

---

## 📊 Exemple de Données Affichées

### **Pour un Administrateur**

```
Statistiques Globales:
- 50 propriétés (35 occupées, 15 libres)
- 42 locataires actifs (45 au total)
- 38 baux actifs (5 expirant bientôt)
- 12 messages non lus (45 conversations)

Finances du Mois:
- Revenus: 125,000 FCFA
- Dépenses: 35,000 FCFA
- Bénéfice Net: 90,000 FCFA
- 8 paiements en attente (3 en retard)
```

### **Pour un Gestionnaire**

```
Mes Statistiques:
- 8 propriétés (6 occupées, 2 libres)
- 7 locataires actifs (8 au total)
- 6 baux actifs (1 expirant bientôt)
- 3 messages non lus (12 conversations)

Mes Finances:
- Revenus du Mois: 25,000 FCFA
- 2 paiements en attente (1 en retard)
- 1 demande de maintenance urgente
```

### **Pour un Locataire**

```
Mes Informations:
- 1 propriété louée
- 1 bail actif (expire dans 8 mois)
- 1 paiement en attente
- 2 messages non lus (5 conversations)

Mon Compte:
- Solde: -12,500 FCFA (débiteur)
- Crédits du Mois: 0 FCFA
- Débits du Mois: 12,500 FCFA
- 1 paiement en retard
```

---

## 🔐 Sécurité et Isolation des Données

### **Principe de Séparation**

Chaque rôle ne voit **UNIQUEMENT** ses propres données :

1. **Administrateur** : Accès à TOUTES les données
2. **Gestionnaire** : Accès uniquement aux données de ses propriétés
3. **Locataire** : Accès uniquement à ses propres données

### **Mécanismes de Filtrage**

#### **Pour les Gestionnaires**
```php
$owner = $user->getOwner();
if ($owner) {
    $managerProperties = $propertyRepo->findBy(['owner' => $owner]);
    // Toutes les requêtes filtrent par $owner->getId()
}
```

#### **Pour les Locataires**
```php
$tenant = $user->getTenant();
if ($tenant) {
    $tenantProperties = $propertyRepo->findByTenantWithFilters($tenant->getId());
    // Toutes les requêtes filtrent par $tenant->getId()
}
```

---

## 🚀 Avantages du Système

### **Pour l'Expérience Utilisateur**

1. **Personnalisation** : Interface adaptée à chaque rôle
2. **Pertinence** : Informations contextuelles
3. **Simplicité** : Actions rapides accessibles
4. **Clarté** : Données organisées et lisibles

### **Pour la Maintenance**

1. **Modularité** : Code séparé par rôle
2. **Extensibilité** : Facile d'ajouter de nouvelles fonctionnalités
3. **Testabilité** : Chaque dashboard est indépendant
4. **Réutilisabilité** : Méthodes de repository réutilisables

### **Pour la Sécurité**

1. **Isolation** : Données isolées par rôle
2. **Filtrage** : Filtrage systématique des données
3. **Validation** : Vérifications de sécurité
4. **Traçabilité** : Actions identifiables par utilisateur

---

## 📝 Fichiers Modifiés

### **Contrôleurs**
- ✅ `src/Controller/DashboardController.php` (refactorisé)

### **Templates**
- ✅ `templates/dashboard/admin.html.twig` (créé)
- ✅ `templates/dashboard/manager.html.twig` (créé)
- ✅ `templates/dashboard/tenant.html.twig` (créé)

### **Repositories**
- ✅ `src/Repository/PaymentRepository.php` (méthodes ajoutées)
- ✅ `src/Repository/MaintenanceRequestRepository.php` (méthodes ajoutées)
- ✅ `src/Repository/LeaseRepository.php` (méthodes ajoutées)

---

## 🧪 Tests Recommandés

### **Tests Fonctionnels**

1. **Test Admin** : Connexion en tant qu'admin et vérification des statistiques globales
2. **Test Gestionnaire** : Connexion en tant que gestionnaire et vérification des données filtrées
3. **Test Locataire** : Connexion en tant que locataire et vérification de l'isolation des données

### **Tests de Sécurité**

1. **Isolation des Données** : Vérifier qu'un gestionnaire ne voit pas les propriétés des autres
2. **Isolation des Paiements** : Vérifier qu'un locataire ne voit que ses paiements
3. **Isolation des Messages** : Vérifier que les conversations sont filtrées correctement

---

## 🎯 Prochaines Évolutions Possibles

### **Fonctionnalités Suggérées**

1. **Graphiques** : Ajouter des graphiques pour visualiser les tendances
2. **Export** : Permettre l'export des données en PDF/Excel
3. **Notifications** : Ajouter des notifications en temps réel
4. **Personnalisation** : Permettre à chaque utilisateur de personnaliser son dashboard
5. **Widgets** : Ajouter des widgets drag-and-drop

### **Améliorations Techniques**

1. **Cache** : Mettre en cache les statistiques pour améliorer les performances
2. **Pagination** : Paginer les listes d'activités récentes
3. **Filtres** : Ajouter des filtres avancés sur les données
4. **API** : Créer une API pour les dashboards mobiles
5. **WebSockets** : Implémenter des mises à jour en temps réel

---

## 📞 Guide d'Utilisation

### **Pour les Administrateurs**

1. Connectez-vous avec un compte administrateur
2. Vous serez redirigé vers le dashboard admin
3. Consultez les statistiques globales
4. Utilisez les actions rapides pour créer de nouvelles ressources
5. Surveillez les alertes urgentes

### **Pour les Gestionnaires**

1. Connectez-vous avec un compte gestionnaire
2. Vous verrez uniquement vos propriétés et locataires
3. Consultez vos revenus mensuels
4. Gérez les demandes de maintenance de vos propriétés
5. Contactez vos locataires via la messagerie

### **Pour les Locataires**

1. Connectez-vous avec votre compte locataire
2. Consultez votre solde comptable
3. Vérifiez vos paiements en attente
4. Créez des demandes de maintenance
5. Contactez votre gestionnaire

---

## ⚠️ Notes Importantes

### **Entité Property**

L'entité `Property` utilise le champ `address` (et non `name`). Tous les templates ont été mis à jour pour utiliser :
```twig
{{ property.address }}  // ✅ Correct
{{ property.name }}     // ❌ Incorrect
```

### **Gestion des Utilisateurs sans Association**

Si un utilisateur n'a pas d'association `Owner` ou `Tenant` :
- Le dashboard affiche des statistiques à zéro
- Aucune erreur n'est levée
- Un message d'information peut être affiché

### **Performance**

Pour un grand nombre de données :
- Considérer la mise en cache des statistiques
- Limiter le nombre d'activités récentes affichées
- Utiliser des requêtes optimisées avec les index appropriés

---

**Date de création :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et testé
