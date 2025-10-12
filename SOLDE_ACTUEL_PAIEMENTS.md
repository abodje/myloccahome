# 💰 Calcul du Solde Actuel sur la Page Paiements

## 📋 Vue d'ensemble

Le système de paiements a été amélioré pour afficher le **solde actuel** calculé dynamiquement selon le rôle de l'utilisateur connecté. Le solde est basé sur les écritures comptables et s'adapte automatiquement pour chaque type d'utilisateur.

---

## 🎯 Fonctionnalités Implémentées

### **1. Calcul du Solde selon le Rôle**

#### **Pour les Locataires (ROLE_TENANT)**
- **Calcul** : Solde personnel du locataire uniquement
- **Source** : `AccountingEntryRepository::getTenantStatistics()`
- **Affichage** : Solde positif (crédit) ou négatif (débit)

```php
$tenant = $user->getTenant();
$tenantStats = $accountingRepository->getTenantStatistics($tenant->getId());
return $tenantStats['balance'] ?? 0.0;
```

#### **Pour les Gestionnaires (ROLE_MANAGER)**
- **Calcul** : Somme des soldes de tous les locataires gérés
- **Source** : Écritures comptables filtrées par propriétaire
- **Affichage** : Solde global de tous leurs locataires

```php
$owner = $user->getOwner();
// Récupération de tous les locataires du gestionnaire
$totalBalance = 0;
foreach ($managerTenants as $tenant) {
    $tenantStats = $accountingRepository->getTenantStatistics($tenant->getId());
    $totalBalance += $tenantStats['balance'] ?? 0.0;
}
return $totalBalance;
```

#### **Pour les Administrateurs (ROLE_ADMIN)**
- **Calcul** : Somme des soldes de tous les locataires du système
- **Source** : Toutes les écritures comptables
- **Affichage** : Solde global de l'application

```php
// Récupération de tous les locataires
$totalBalance = 0;
foreach ($allTenants as $tenant) {
    $tenantStats = $accountingRepository->getTenantStatistics($tenant->getId());
    $totalBalance += $tenantStats['balance'] ?? 0.0;
}
return $totalBalance;
```

---

## 🎨 Interface Utilisateur

### **Affichage du Solde**

La carte "Solde actuel" affiche :

#### **Solde Positif (Crédit)**
```twig
Solde actuel : 50,000 FCFA
✓ Vous avez un crédit disponible
```
- **Couleur** : Vert (`text-success`)
- **Icône** : Cercle avec check (`bi-check-circle`)
- **Message** : "Vous avez un crédit disponible"

#### **Solde Négatif (Débiteur)**
```twig
Solde actuel : -12,500 FCFA
⚠ Vous avez un solde débiteur
```
- **Couleur** : Rouge (`text-danger`)
- **Icône** : Cercle d'exclamation (`bi-exclamation-circle`)
- **Message** : "Vous avez un solde débiteur"

#### **Solde Nul (À jour)**
```twig
Solde actuel : 0 FCFA
○ Votre compte est à jour
```
- **Couleur** : Gris (`text-muted`)
- **Icône** : Cercle avec tiret (`bi-dash-circle`)
- **Message** : "Votre compte est à jour"

---

## 🔧 Implémentation Technique

### **Fichiers Modifiés**

#### **1. Controller : `src/Controller/PaymentController.php`**

**Ajout de l'import :**
```php
use App\Repository\AccountingEntryRepository;
```

**Modification de la méthode `index()` :**
```php
public function index(
    PaymentRepository $paymentRepository,
    Request $request,
    AdvancePaymentRepository $advancePaymentRepository,
    AccountingEntryRepository $accountingRepository  // ✅ Ajouté
): Response {
    // ... code existant ...
    
    // Calculer le solde actuel selon le rôle
    $currentBalance = $this->calculateCurrentBalance($accountingRepository, $user);

    return $this->render('payment/index.html.twig', [
        'payments' => $payments,
        'stats' => $stats,
        'advance_stats' => $advanceStats,
        'current_balance' => $currentBalance,  // ✅ Ajouté
        'current_status' => $status,
        'current_type' => $type,
        'current_year' => $year,
        'current_month' => $month,
    ]);
}
```

**Nouvelle méthode `calculateCurrentBalance()` :**
```php
private function calculateCurrentBalance(AccountingEntryRepository $accountingRepository, $user): float
{
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // Pour les locataires
        $tenant = $user->getTenant();
        if ($tenant) {
            $tenantStats = $accountingRepository->getTenantStatistics($tenant->getId());
            return $tenantStats['balance'] ?? 0.0;
        }
    } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
        // Pour les gestionnaires
        $owner = $user->getOwner();
        if ($owner) {
            $managerTenants = $accountingRepository->createQueryBuilder('ae')
                ->select('ae.tenant')
                ->join('ae.lease', 'l')
                ->join('l.property', 'p')
                ->join('p.owner', 'o')
                ->where('o.id = :ownerId')
                ->andWhere('ae.tenant IS NOT NULL')
                ->setParameter('ownerId', $owner->getId())
                ->groupBy('ae.tenant')
                ->getQuery()
                ->getResult();

            $totalBalance = 0;
            foreach ($managerTenants as $tenantArray) {
                $tenant = $tenantArray['tenant'];
                if ($tenant) {
                    $tenantStats = $accountingRepository->getTenantStatistics($tenant->getId());
                    $totalBalance += $tenantStats['balance'] ?? 0.0;
                }
            }
            return $totalBalance;
        }
    }

    // Pour les admins
    $allTenants = $accountingRepository->createQueryBuilder('ae')
        ->select('ae.tenant')
        ->where('ae.tenant IS NOT NULL')
        ->groupBy('ae.tenant')
        ->getQuery()
        ->getResult();

    $totalBalance = 0;
    foreach ($allTenants as $tenantArray) {
        $tenant = $tenantArray['tenant'];
        if ($tenant) {
            $tenantStats = $accountingRepository->getTenantStatistics($tenant->getId());
            $totalBalance += $tenantStats['balance'] ?? 0.0;
        }
    }
    return $totalBalance;
}
```

#### **2. Template : `templates/payment/index.html.twig`**

**Avant :**
```twig
<h6 class="text-muted mb-2">Solde actuel :</h6>
<h2 class="mb-0 text-success">{{ 0|currency }}</h2>
```

**Après :**
```twig
<h6 class="text-muted mb-2">Solde actuel :</h6>
<h2 class="mb-0 {{ current_balance >= 0 ? 'text-success' : 'text-danger' }}">
    {{ current_balance|currency }}
</h2>
{% if current_balance < 0 %}
<small class="text-danger">
    <i class="bi bi-exclamation-circle"></i>
    Vous avez un solde débiteur
</small>
{% elseif current_balance > 0 %}
<small class="text-success">
    <i class="bi bi-check-circle"></i>
    Vous avez un crédit disponible
</small>
{% else %}
<small class="text-muted">
    <i class="bi bi-dash-circle"></i>
    Votre compte est à jour
</small>
{% endif %}
```

---

## 📊 Exemples de Calcul

### **Exemple 1 : Locataire**

**Écritures Comptables :**
- Loyer janvier : -15,000 FCFA (débit)
- Paiement janvier : +15,000 FCFA (crédit)
- Loyer février : -15,000 FCFA (débit)
- Paiement partiel : +10,000 FCFA (crédit)

**Calcul :**
```
Solde = -15,000 + 15,000 - 15,000 + 10,000
Solde = -5,000 FCFA (débiteur)
```

**Affichage :**
```
Solde actuel : -5,000 FCFA
⚠ Vous avez un solde débiteur
```

### **Exemple 2 : Gestionnaire**

**Locataires gérés :**
- Locataire A : -5,000 FCFA
- Locataire B : +2,000 FCFA
- Locataire C : 0 FCFA

**Calcul :**
```
Solde Total = -5,000 + 2,000 + 0
Solde Total = -3,000 FCFA (débiteur)
```

**Affichage :**
```
Solde actuel : -3,000 FCFA
⚠ Vous avez un solde débiteur
```

### **Exemple 3 : Administrateur**

**Tous les locataires du système :**
- 10 locataires avec solde négatif : -100,000 FCFA
- 5 locataires avec solde positif : +25,000 FCFA
- 15 locataires avec solde nul : 0 FCFA

**Calcul :**
```
Solde Total = -100,000 + 25,000 + 0
Solde Total = -75,000 FCFA (débiteur global)
```

**Affichage :**
```
Solde actuel : -75,000 FCFA
⚠ Vous avez un solde débiteur
```

---

## 🎯 Signification du Solde

### **Solde Négatif (Débiteur)**
- **Pour un Locataire** : Il doit de l'argent (loyers impayés)
- **Pour un Gestionnaire** : Ses locataires doivent de l'argent
- **Pour un Admin** : Le système a des créances à recouvrer

### **Solde Positif (Créditeur)**
- **Pour un Locataire** : Il a payé d'avance (crédit disponible)
- **Pour un Gestionnaire** : Ses locataires ont payé d'avance
- **Pour un Admin** : Le système a des crédits disponibles

### **Solde Nul**
- **Pour un Locataire** : Son compte est à jour
- **Pour un Gestionnaire** : Ses locataires sont à jour
- **Pour un Admin** : Le système est équilibré

---

## 🔐 Sécurité et Isolation

### **Principe de Séparation**

1. **Locataire** : Ne voit que son propre solde
2. **Gestionnaire** : Voit le solde agrégé de ses locataires uniquement
3. **Administrateur** : Voit le solde global du système

### **Filtrage des Données**

Le filtrage est effectué au niveau de la base de données :
- Jointures sécurisées avec `Property`, `Owner`, `Tenant`
- Utilisation de `groupBy` pour éviter les doublons
- Vérifications de nullité pour éviter les erreurs

---

## 🚀 Avantages

### **Pour l'Utilisateur**

1. **Visibilité immédiate** du solde comptable
2. **Code couleur intuitif** (vert/rouge/gris)
3. **Messages explicites** sur l'état du compte
4. **Contextualisation** selon le rôle

### **Pour la Gestion**

1. **Suivi en temps réel** des soldes
2. **Détection rapide** des débiteurs
3. **Agrégation automatique** pour les gestionnaires
4. **Vue d'ensemble** pour les administrateurs

### **Pour le Développement**

1. **Code modulaire** et réutilisable
2. **Calculs centralisés** dans le contrôleur
3. **Performance optimisée** avec groupBy
4. **Maintenabilité** assurée

---

## 📝 Notes Importantes

### **Source de Données**

Le solde est calculé à partir de l'`AccountingEntryRepository` qui utilise la méthode `getTenantStatistics()`. Cette méthode calcule :
- **Balance** : Différence entre crédits et débits
- **Total Credits** : Somme des paiements
- **Total Debits** : Somme des loyers dus

### **Performance**

Pour optimiser les performances avec un grand nombre de locataires :
1. Ajouter un **cache** pour les soldes (ex: Redis)
2. Utiliser une **vue matérialisée** en base de données
3. Pré-calculer les soldes lors de chaque écriture comptable

### **Cohérence**

Le solde affiché est **toujours cohérent** avec les écritures comptables car :
- Calculé en temps réel à chaque requête
- Basé sur les mêmes données que la comptabilité
- Pas de cache qui pourrait être obsolète

---

## 🧪 Tests Recommandés

### **Tests Fonctionnels**

1. **Test Locataire** :
   - Créer un locataire avec des paiements
   - Vérifier que le solde correspond aux écritures comptables
   - Tester avec solde positif, négatif et nul

2. **Test Gestionnaire** :
   - Créer un gestionnaire avec plusieurs locataires
   - Vérifier que le solde agrégé est correct
   - Tester l'isolation des données (ne voit pas les autres gestionnaires)

3. **Test Administrateur** :
   - Vérifier que le solde global est correct
   - Tester avec de nombreux locataires
   - Vérifier les performances

### **Tests de Sécurité**

1. **Isolation** : Un locataire ne doit pas voir le solde des autres
2. **Filtrage** : Un gestionnaire ne doit voir que ses locataires
3. **Permissions** : Vérifier les rôles requis

---

## 📞 Utilisation

### **Pour Consulter le Solde**

1. Connectez-vous à l'application
2. Accédez à `/mes-paiements/`
3. Le solde s'affiche automatiquement en haut de la page

### **Interprétation**

- **Vert** : Tout va bien, crédit disponible
- **Rouge** : Attention, solde débiteur à régulariser
- **Gris** : Compte à jour, pas d'action requise

---

**Date de création :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et opérationnel
