# 🔒 Filtrage des Paiements par Rôle Utilisateur

## 📋 Vue d'ensemble

Le système de paiements filtre désormais automatiquement l'affichage des paiements selon le rôle de l'utilisateur connecté, garantissant que chaque utilisateur ne voit que les informations pertinentes.

---

## 🎯 Fonctionnalités Implémentées

### 1️⃣ **Pour les LOCATAIRES** (`ROLE_TENANT`)
- ✅ Affichage uniquement de **leurs propres paiements**
- ✅ Statistiques calculées sur leurs paiements personnels
- ✅ Accès aux acomptes liés à leurs baux
- ✅ Filtrage automatique par locataire connecté

**Route :** `/mes-paiements/`

**Ce qui est affiché :**
- Loyers du locataire
- Dépôts de garantie
- Charges
- Pénalités de retard
- Acomptes effectués

### 2️⃣ **Pour les GESTIONNAIRES** (`ROLE_MANAGER`)
- ✅ Affichage des paiements des **locataires qu'ils gèrent**
- ✅ Filtrage basé sur les propriétés possédées
- ✅ Statistiques sur l'ensemble de leur portefeuille
- ✅ Accès aux acomptes de leurs locataires

**Route :** `/mes-paiements/`

**Ce qui est affiché :**
- Paiements de tous les locataires des propriétés du gestionnaire
- Revenus générés par leurs propriétés
- Statistiques globales de leur portefeuille

### 3️⃣ **Pour les ADMINISTRATEURS** (`ROLE_ADMIN`)
- ✅ Affichage de **tous les paiements** du système
- ✅ Statistiques globales complètes
- ✅ Accès illimité à toutes les données

**Route :** `/mes-paiements/`

**Ce qui est affiché :**
- Tous les paiements de tous les locataires
- Statistiques globales du système
- Vue d'ensemble complète

---

## 🛠️ Modifications Techniques

### **1. PaymentController.php**

#### **Méthode `index()` mise à jour**
```php
public function index(
    PaymentRepository $paymentRepository,
    Request $request,
    AdvancePaymentRepository $advancePaymentRepository
): Response {
    /** @var \App\Entity\User|null $user */
    $user = $this->getUser();
    
    // Filtrage selon le rôle
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // Locataire : ses paiements uniquement
        $tenant = $user->getTenant();
        if ($tenant) {
            $payments = $paymentRepository->findByTenantWithFilters(
                $tenant->getId(), $status, $type, $year, $month
            );
        }
    } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
        // Gestionnaire : paiements de ses locataires
        $owner = $user->getOwner();
        if ($owner) {
            $payments = $paymentRepository->findByManagerWithFilters(
                $owner->getId(), $status, $type, $year, $month
            );
        }
    } else {
        // Admin : tous les paiements
        $payments = $paymentRepository->findWithFilters($status, $type, $year, $month);
    }
    
    // Statistiques filtrées
    $stats = $this->calculateFilteredStats($paymentRepository, $user);
    $advanceStats = $this->calculateFilteredAdvanceStats($advancePaymentRepository, $user);
}
```

#### **Nouvelle méthode : `calculateFilteredStats()`**
Calcule les statistiques filtrées selon le rôle :
- `total_pending` : Paiements en attente
- `total_paid` : Paiements effectués
- `total_overdue` : Paiements en retard
- `monthly_income` : Revenus mensuels

#### **Nouvelle méthode : `calculateFilteredAdvanceStats()`**
Calcule les statistiques des acomptes filtrées :
- `total` : Nombre total d'acomptes
- `available_balance` : Solde disponible
- `used_balance` : Solde utilisé

---

### **2. PaymentRepository.php**

#### **Nouvelle méthode : `findByTenantWithFilters()`**
```php
public function findByTenantWithFilters(
    int $tenantId, 
    ?string $status = null, 
    ?string $type = null, 
    ?int $year = null, 
    ?int $month = null
): array {
    $qb = $this->createQueryBuilder('p')
        ->join('p.lease', 'l')
        ->join('l.tenant', 't')
        ->where('t.id = :tenantId')
        ->setParameter('tenantId', $tenantId);
    
    // Filtres additionnels (status, type, date)
    // ...
    
    return $qb->orderBy('p.dueDate', 'DESC')
              ->getQuery()
              ->getResult();
}
```

**Récupère les paiements d'un locataire spécifique avec filtres optionnels**

#### **Nouvelle méthode : `findByManagerWithFilters()`**
```php
public function findByManagerWithFilters(
    int $ownerId, 
    ?string $status = null, 
    ?string $type = null, 
    ?int $year = null, 
    ?int $month = null
): array {
    $qb = $this->createQueryBuilder('p')
        ->join('p.lease', 'l')
        ->join('l.property', 'prop')
        ->join('prop.owner', 'o')
        ->where('o.id = :ownerId')
        ->setParameter('ownerId', $ownerId);
    
    // Filtres additionnels
    // ...
    
    return $qb->orderBy('p.dueDate', 'DESC')
              ->getQuery()
              ->getResult();
}
```

**Récupère les paiements des locataires gérés par un propriétaire/gestionnaire**

---

## 🔐 Sécurité

### **Isolation des Données**
- ✅ **Locataires** : Ne peuvent voir que leurs propres données
- ✅ **Gestionnaires** : Accès limité à leurs propriétés
- ✅ **Admins** : Accès complet

### **Requêtes Filtrées**
- Les requêtes SQL incluent automatiquement les clauses WHERE appropriées
- Utilisation de jointures pour garantir l'isolation des données
- Aucune donnée sensible n'est exposée à des utilisateurs non autorisés

---

## 📊 Statistiques Filtrées

### **Pour Locataires**
```php
$stats = [
    'total_pending' => 2,      // 2 paiements en attente
    'total_paid' => 12,         // 12 paiements effectués
    'total_overdue' => 1,       // 1 paiement en retard
    'monthly_income' => 0       // Non applicable pour locataires
];

$advanceStats = [
    'total' => 1,               // 1 acompte effectué
    'available_balance' => 5000,// 5000 XOF disponibles
    'used_balance' => 15000     // 15000 XOF déjà utilisés
];
```

### **Pour Gestionnaires**
```php
$stats = [
    'total_pending' => 15,      // 15 paiements en attente (tous locataires)
    'total_paid' => 89,         // 89 paiements effectués
    'total_overdue' => 3,       // 3 paiements en retard
    'monthly_income' => 450000  // 450 000 XOF de revenus mensuels
];

$advanceStats = [
    'total' => 8,               // 8 acomptes au total
    'available_balance' => 35000,
    'used_balance' => 85000
];
```

### **Pour Admins**
- Statistiques globales de tous les utilisateurs du système

---

## 🎮 Test de la Fonctionnalité

### **1. Test en tant que Locataire**
```bash
# Se connecter avec un compte locataire
# Naviguer vers /mes-paiements/
```

**Résultat attendu :**
- Liste des paiements du locataire uniquement
- Statistiques personnelles
- Boutons "Payer en ligne" pour les paiements en attente

### **2. Test en tant que Gestionnaire**
```bash
# Se connecter avec un compte gestionnaire
# Naviguer vers /mes-paiements/
```

**Résultat attendu :**
- Liste des paiements de tous les locataires gérés
- Statistiques globales du portefeuille
- Accès à toutes les actions (marquer payé, générer loyers, etc.)

### **3. Test en tant qu'Admin**
```bash
# Se connecter avec un compte admin
# Naviguer vers /mes-paiements/
```

**Résultat attendu :**
- Liste complète de tous les paiements du système
- Statistiques globales
- Accès complet à toutes les fonctionnalités

---

## 🔄 Relations entre Entités

```
User (ROLE_TENANT)
  └─> Tenant
       └─> Lease(s)
            └─> Payment(s)
            └─> AdvancePayment(s)

User (ROLE_MANAGER)
  └─> Owner
       └─> Property(ies)
            └─> Lease(s)
                 └─> Payment(s)
                 └─> AdvancePayment(s)
```

---

## ✅ Corrections Appliquées

### **Problème résolu : `Unrecognized field: App\Entity\AdvancePayment::$tenant`**

**Cause :** L'entité `AdvancePayment` n'a pas de relation directe avec `Tenant`, seulement avec `Lease`.

**Solution :** Utiliser une jointure via `Lease` pour accéder aux acomptes du locataire :

```php
$tenantAdvances = $advancePaymentRepository->createQueryBuilder('ap')
    ->join('ap.lease', 'l')
    ->where('l.tenant = :tenant')
    ->setParameter('tenant', $tenant)
    ->getQuery()
    ->getResult();
```

### **Problème résolu : Méthodes `getTenant()` et `getOwner()` non reconnues**

**Cause :** `getUser()` retourne `UserInterface`, pas notre classe `User` spécifique.

**Solution :** Ajout d'une annotation PHPDoc pour indiquer le type exact :

```php
/** @var \App\Entity\User|null $user */
$user = $this->getUser();
```

---

## 📝 Fichiers Modifiés

1. ✅ **src/Controller/PaymentController.php**
   - Méthode `index()` mise à jour
   - Ajout de `calculateFilteredStats()`
   - Ajout de `calculateFilteredAdvanceStats()`

2. ✅ **src/Repository/PaymentRepository.php**
   - Ajout de `findByTenantWithFilters()`
   - Ajout de `findByManagerWithFilters()`

---

## 🚀 Prochaines Étapes Possibles

### **Améliorations futures :**
1. **Filtrage des autres pages :**
   - `/mes-biens/` pour les gestionnaires
   - `/mes-documents/` par rôle (déjà implémenté)
   - `/ma-comptabilite/` par rôle

2. **Notifications :**
   - Rappels de paiement uniquement pour les locataires concernés
   - Alertes pour les gestionnaires sur les retards de leurs locataires

3. **Exports :**
   - Export PDF/Excel des paiements filtrés par rôle
   - Rapports personnalisés selon le rôle

---

## 📞 Support

Pour toute question ou problème concernant le filtrage des paiements, vérifiez que :
- ✅ L'utilisateur est bien connecté
- ✅ L'utilisateur a le bon rôle assigné
- ✅ Un `Tenant` ou `Owner` est bien lié au compte `User`
- ✅ Le cache Symfony est vidé (`php bin/console cache:clear`)

---

**Date de mise à jour :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et testé

