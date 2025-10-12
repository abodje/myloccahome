# 🔧 Menu "Mes demandes" pour les Locataires

## 📋 Vue d'ensemble

Le menu "Mes demandes" filtre maintenant automatiquement les demandes de maintenance selon le rôle de l'utilisateur connecté, garantissant que chaque utilisateur ne voit que les demandes pertinentes à son rôle.

---

## ✅ Modifications Apportées

### **1. Contrôleur Demandes (`MaintenanceRequestController.php`)**

**Modification :** Filtrage des demandes selon le rôle de l'utilisateur

#### **Nouvelle logique de filtrage :**

```php
public function index(MaintenanceRequestRepository $maintenanceRequestRepository, Request $request): Response
{
    /** @var \App\Entity\User|null $user */
    $user = $this->getUser();
    
    // Filtrer selon le rôle
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // LOCATAIRE : ses demandes uniquement
        $tenant = $user->getTenant();
        if ($tenant) {
            $requests = $maintenanceRequestRepository->findByTenantWithFilters($tenant->getId(), ...);
        }
    } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
        // GESTIONNAIRE : demandes de ses propriétés
        $owner = $user->getOwner();
        if ($owner) {
            $requests = $maintenanceRequestRepository->findByManagerWithFilters($owner->getId(), ...);
        }
    } else {
        // ADMIN : toutes les demandes
        $requests = $maintenanceRequestRepository->findWithFilters(...);
    }
    
    $stats = $this->calculateFilteredStats($maintenanceRequestRepository, $user);
    $isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());
}
```

#### **Fonctionnalités par rôle :**

| Rôle | Demandes Affichées | Statistiques | Actions Disponibles |
|------|-------------------|--------------|-------------------|
| **LOCATAIRE** | Ses demandes uniquement | Personnelles | Création + Consultation |
| **GESTIONNAIRE** | Demandes de ses propriétés | De son portefeuille | Gestion complète |
| **ADMIN** | Toutes les demandes | Globales | Toutes les actions |

---

### **2. Repository Demandes (`MaintenanceRequestRepository.php`)**

**Nouvelles méthodes ajoutées :**

#### **`findWithFilters()` - Méthode générique**
```php
public function findWithFilters(?string $status = null, ?string $priority = null, ?string $category = null): array
{
    $qb = $this->createQueryBuilder('mr');
    
    // Filtres conditionnels
    if ($status) {
        $qb->andWhere('mr.status = :status')->setParameter('status', $status);
    }
    // ...
}
```

#### **`findByTenantWithFilters()` - Pour les locataires**
```php
public function findByTenantWithFilters(int $tenantId, ?string $status = null, ?string $priority = null, ?string $category = null): array
{
    $qb = $this->createQueryBuilder('mr')
        ->join('mr.property', 'p')
        ->join('p.leases', 'l')
        ->where('l.tenant = :tenantId')
        ->andWhere('l.status = :leaseStatus')
        ->setParameter('tenantId', $tenantId)
        ->setParameter('leaseStatus', 'active');
    
    // Filtres additionnels
    // ...
}
```

**Logique de filtrage :** Recherche les demandes liées aux propriétés avec des baux actifs du locataire

#### **`findByManagerWithFilters()` - Pour les gestionnaires**
```php
public function findByManagerWithFilters(int $ownerId, ?string $status = null, ?string $priority = null, ?string $category = null): array
{
    $qb = $this->createQueryBuilder('mr')
        ->join('mr.property', 'p')
        ->where('p.owner = :ownerId')
        ->setParameter('ownerId', $ownerId);
    
    // Filtres additionnels
    // ...
}
```

**Logique de filtrage :** Recherche les demandes liées aux propriétés du gestionnaire

---

### **3. Méthode `calculateFilteredStats()`**

**Ajoutée au contrôleur pour calculer les statistiques filtrées :**

```php
private function calculateFilteredStats(MaintenanceRequestRepository $maintenanceRequestRepository, $user): array
{
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // Pour les locataires, calculer les stats sur leurs demandes seulement
        $tenantRequests = $maintenanceRequestRepository->findByTenantWithFilters($tenant->getId());
        
        $stats = [
            'total' => count($tenantRequests),
            'pending' => 0,      // En attente
            'urgent' => 0,       // En cours
            'overdue' => 0,      // En retard
            'completed' => 0     // Terminées
        ];
        
        // Calcul des statistiques par statut
        // ...
    }
    // Même logique pour les gestionnaires et admins
}
```

---

## 🎯 Résultat Final

### **Pour un LOCATAIRE connecté :**

#### **Page "Mes demandes" :**
- **Demandes affichées :** Uniquement celles liées à ses propriétés louées
- **Statistiques :** Totaux personnels (ses demandes en attente, en cours, terminées)
- **Actions disponibles :**
  - ✅ Créer de nouvelles demandes
  - ✅ Consulter ses demandes
  - ✅ Filtrer par statut/priorité/catégorie
  - ✅ Suivre l'évolution de ses demandes

#### **Exemple d'affichage pour un locataire :**
```
Mes demandes personnelles :
┌─────────────────────────────────────────┐
│ 🔧 Fuite d'eau - Appartement T3        │
│ 📍 123 Rue de la Paix, Paris           │
│ 📅 Créée le 10/10/2025                 │
│ 🟡 En cours                            │
│ 👤 Assignée à : Plombier Dupont        │
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│ 🔧 Problème de chauffage - T3          │
│ 📍 123 Rue de la Paix, Paris           │
│ 📅 Créée le 08/10/2025                 │
│ 🟢 Terminée                            │
│ ✅ Résolue le 09/10/2025               │
└─────────────────────────────────────────┘
```

---

## 🔒 Sécurité et Isolation

### **Isolation des Données**
- ✅ **Locataires** : Ne voient que leurs propres demandes
- ✅ **Gestionnaires** : Voient les demandes de leurs propriétés
- ✅ **Admins** : Voient toutes les demandes

### **Logique de Filtrage**
Les demandes sont filtrées selon :
1. **Pour locataires** : Demandes liées aux propriétés avec baux actifs
2. **Pour gestionnaires** : Demandes liées à leurs propriétés
3. **Pour admins** : Aucun filtre (accès total)

### **Relations entre Entités**
```
User (ROLE_TENANT)
  └─> Tenant
       └─> Lease(s) [status = 'active']
            └─> Property(ies)
                 └─> MaintenanceRequest(s)

User (ROLE_MANAGER)
  └─> Owner
       └─> Property(ies)
            └─> MaintenanceRequest(s)
```

---

## 🎮 Test de la Fonctionnalité

### **1. Test en tant que Locataire**
```bash
# Se connecter avec un compte locataire
# Naviguer vers /mes-demandes/
```

**Résultat attendu :**
- Page affiche uniquement les demandes du locataire
- Statistiques personnelles
- Bouton "Faire une demande" disponible
- Possibilité de filtrer ses demandes

### **2. Test en tant que Gestionnaire**
```bash
# Se connecter avec un compte gestionnaire
# Naviguer vers /mes-demandes/
```

**Résultat attendu :**
- Page affiche les demandes de ses propriétés
- Statistiques de son portefeuille
- Toutes les actions de gestion disponibles

### **3. Test en tant qu'Admin**
```bash
# Se connecter avec un compte admin
# Naviguer vers /mes-demandes/
```

**Résultat attendu :**
- Page affiche toutes les demandes du système
- Statistiques globales
- Accès complet à toutes les fonctionnalités

---

## 📊 Statistiques Filtrées

### **Pour Locataires :**
```php
$stats = [
    'total' => 3,           // 3 demandes au total
    'pending' => 1,         // 1 en attente
    'urgent' => 1,          // 1 en cours
    'overdue' => 0,         // 0 en retard
    'completed' => 1        // 1 terminée
];
```

### **Pour Gestionnaires :**
```php
$stats = [
    'total' => 15,          // 15 demandes sur ses propriétés
    'pending' => 5,         // 5 en attente
    'urgent' => 3,          // 3 en cours
    'overdue' => 1,         // 1 en retard
    'completed' => 6        // 6 terminées
];
```

### **Pour Admins :**
- Statistiques globales de toutes les demandes du système

---

## 🔄 Relations et Jointures

### **Requête pour Locataires :**
```sql
SELECT mr.* FROM maintenance_request mr
JOIN property p ON mr.property_id = p.id
JOIN lease l ON p.id = l.property_id
WHERE l.tenant_id = ? 
AND l.status = 'active'
```

### **Requête pour Gestionnaires :**
```sql
SELECT mr.* FROM maintenance_request mr
JOIN property p ON mr.property_id = p.id
WHERE p.owner_id = ?
```

### **Requête pour Admins :**
```sql
SELECT mr.* FROM maintenance_request mr
-- Aucun filtre
```

---

## 📝 Fichiers Modifiés

1. ✅ **src/Controller/MaintenanceRequestController.php**
   - Filtrage par rôle dans `index()`
   - Ajout de `calculateFilteredStats()`
   - Passage de `is_tenant_view` au template

2. ✅ **src/Repository/MaintenanceRequestRepository.php**
   - `findWithFilters()` - méthode générique
   - `findByTenantWithFilters()` - pour locataires
   - `findByManagerWithFilters()` - pour gestionnaires

---

## 🚀 Avantages

### **Pour les Locataires :**
- ✅ **Simplicité** : Ne voient que leurs demandes
- ✅ **Suivi** : Peuvent suivre l'évolution de leurs demandes
- ✅ **Communication** : Interface claire pour créer des demandes
- ✅ **Transparence** : Statistiques personnelles

### **Pour les Gestionnaires :**
- ✅ **Vision claire** : Demandes de leurs propriétés
- ✅ **Gestion efficace** : Assignation et suivi des demandes
- ✅ **Priorisation** : Gérer les urgences
- ✅ **Reporting** : Statistiques de leur portefeuille

### **Pour les Admins :**
- ✅ **Vue d'ensemble** : Toutes les demandes du système
- ✅ **Gestion globale** : Supervision complète
- ✅ **Analytics** : Statistiques globales

---

## 📞 Support

Pour tester la fonctionnalité :

1. **Connectez-vous en tant que locataire**
2. **Naviguez vers /mes-demandes/**
3. **Vérifiez que seules vos demandes s'affichent**
4. **Testez la création d'une nouvelle demande**

---

**Date de mise à jour :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et testé
