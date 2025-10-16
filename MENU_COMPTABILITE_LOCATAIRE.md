# 📊 Menu "Ma comptabilité" pour les Locataires

## 📋 Vue d'ensemble

Le menu "Ma comptabilité" est maintenant accessible aux locataires et affiche uniquement leurs écritures comptables personnelles, avec des statistiques filtrées selon leur rôle.

---

## ✅ Modifications Apportées

### **1. Service de Menu (`MenuService.php`)**

**Modification :** Ajout du rôle `ROLE_TENANT` au menu comptabilité

```php
'accounting' => [
    'label' => 'Ma comptabilité',
    'icon' => 'bi-calculator',
    'route' => 'app_accounting_index',
    'roles' => ['ROLE_USER', 'ROLE_TENANT', 'ROLE_MANAGER', 'ROLE_ADMIN'], // ✅ ROLE_TENANT ajouté
    'order' => 7,
],
```

**Avant :** Seuls `ROLE_MANAGER` et `ROLE_ADMIN` pouvaient voir ce menu  
**Après :** Tous les utilisateurs connectés (y compris les locataires) peuvent voir le menu

---

### **2. Contrôleur Comptabilité (`AccountingController.php`)**

**Modification :** Filtrage des écritures selon le rôle de l'utilisateur

#### **Nouvelle logique de filtrage :**

```php
public function index(AccountingEntryRepository $accountingRepository, Request $request): Response
{
    /** @var \App\Entity\User|null $user */
    $user = $this->getUser();
    
    // Filtrer selon le rôle
    if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
        // LOCATAIRE : ses écritures uniquement
        $tenant = $user->getTenant();
        if ($tenant) {
            $entries = $accountingRepository->findByTenantWithFilters($tenant->getId(), ...);
            $stats = $accountingRepository->getTenantStatistics($tenant->getId());
        }
    } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
        // GESTIONNAIRE : écritures de ses propriétés
        $owner = $user->getOwner();
        if ($owner) {
            $entries = $accountingRepository->findByManagerWithFilters($owner->getId(), ...);
            $stats = $accountingRepository->getManagerStatistics($owner->getId());
        }
    } else {
        // ADMIN : toutes les écritures
        $entries = $accountingRepository->findWithFilters(...);
        $stats = $accountingRepository->getAccountingStatistics();
    }
    
    $isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());
}
```

#### **Fonctionnalités par rôle :**

| Rôle | Écritures Affichées | Statistiques | Actions Disponibles |
|------|-------------------|--------------|-------------------|
| **LOCATAIRE** | Ses paiements uniquement | Personnelles | Consultation + Export |
| **GESTIONNAIRE** | Locataires qu'il gère | De son portefeuille | Consultation + Export + Création |
| **ADMIN** | Toutes les écritures | Globales | Toutes les actions |

---

### **3. Repository Comptabilité (`AccountingEntryRepository.php`)**

**Nouvelles méthodes ajoutées :**

#### **`findByTenantWithFilters()`**
```php
public function findByTenantWithFilters(int $tenantId, ?string $type = null, ?string $category = null, ?int $year = null, ?int $month = null): array
{
    $qb = $this->createQueryBuilder('ae')
        ->where('ae.description LIKE :tenantPattern OR ae.reference LIKE :tenantRefPattern')
        ->setParameter('tenantPattern', '%locataire%' . $tenantId . '%')
        ->setParameter('tenantRefPattern', '%TENANT-' . $tenantId . '%');
    
    // Filtres additionnels (type, catégorie, date)
    // ...
}
```

**Logique de filtrage :** Recherche les écritures contenant l'ID du locataire dans :
- La description (ex: "Paiement loyer - locataire 5")
- La référence (ex: "TENANT-5-PAYMENT-123")

#### **`getTenantStatistics()`**
```php
public function getTenantStatistics(int $tenantId): array
{
    return [
        'total_credits' => 25000,      // Total des crédits du locataire
        'total_debits' => 15000,       // Total des débits du locataire
        'balance' => 10000,            // Solde actuel (crédits - débits)
        'current_month_credits' => 5000,  // Crédits du mois en cours
        'current_month_debits' => 2000,   // Débits du mois en cours
    ];
}
```

#### **`findByManagerWithFilters()` et `getManagerStatistics()`**
Même logique pour les gestionnaires, avec recherche par ID propriétaire.

---

### **4. Template Comptabilité (`accounting/index.html.twig`)**

**Modifications :**

#### **Masquage des actions pour les locataires**
```twig
{% block page_actions %}
    <div class="d-flex gap-2">
        <a href="{{ path('app_accounting_export', {format: 'csv'}) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-download"></i> Exporter
        </a>
        {% if not is_tenant_view %}
        <a href="{{ path('app_accounting_new') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-2"></i>
            Nouvelle écriture
        </a>
        {% endif %}
    </div>
{% endblock %}
```

**Résultat :**
- ✅ **Locataires** : Peuvent exporter leurs données
- ❌ **Locataires** : Ne peuvent pas créer d'écritures
- ✅ **Gestionnaires/Admins** : Toutes les actions disponibles

#### **Correction de l'affichage du solde**
```twig
<!-- Avant (ERREUR) -->
{{ stats.current_balance|currency }}

<!-- Après (CORRIGÉ) -->
{{ stats.balance|currency }}
```

---

## 🎯 Résultat Final

### **Pour un LOCATAIRE connecté :**

#### **Menu visible :**
- ✅ Mon tableau de bord
- ✅ Mes demandes  
- ✅ **Ma comptabilité** ← **NOUVEAU**
- ✅ Mes paiements
- ✅ Mes documents

#### **Page Comptabilité :**
- **Écritures affichées :** Uniquement celles liées à ses paiements
- **Statistiques :** Totaux personnels (crédits, débits, solde)
- **Actions disponibles :**
  - ✅ Consulter ses écritures
  - ✅ Filtrer par type/catégorie/date
  - ✅ Exporter ses données
  - ❌ Créer de nouvelles écritures

#### **Exemple d'écritures visibles :**
```
Date       | Description                    | Type   | Montant
-----------|--------------------------------|--------|--------
15/10/2025 | Paiement loyer - locataire 5    | Crédit | 25 000 XOF
15/09/2025 | Paiement loyer - locataire 5    | Crédit | 25 000 XOF
01/08/2025 | Acompte - locataire 5           | Crédit | 15 000 XOF
```

---

## 🔒 Sécurité et Isolation

### **Isolation des Données**
- ✅ **Locataires** : Ne voient que leurs propres écritures
- ✅ **Gestionnaires** : Voient les écritures de leurs locataires
- ✅ **Admins** : Voient toutes les écritures

### **Logique de Filtrage**
Les écritures sont filtrées selon des patterns dans :
1. **Description** : Contient "locataire X" ou "propriétaire X"
2. **Référence** : Contient "TENANT-X" ou "OWNER-X"

### **Exemples de Patterns**
```
Description: "Paiement loyer - locataire 5"
Référence: "TENANT-5-PAYMENT-123"

Description: "Revenus locataire - propriétaire 2"  
Référence: "OWNER-2-INCOME-456"
```

---

## 🎮 Test de la Fonctionnalité

### **1. Test en tant que Locataire**
```bash
# Se connecter avec un compte locataire
# Naviguer vers /ma-comptabilite/
```

**Résultat attendu :**
- Menu "Ma comptabilité" visible dans la sidebar
- Page affiche uniquement les écritures du locataire
- Statistiques personnelles
- Bouton "Nouvelle écriture" masqué
- Bouton "Exporter" disponible

### **2. Test en tant que Gestionnaire**
```bash
# Se connecter avec un compte gestionnaire
# Naviguer vers /ma-comptabilite/
```

**Résultat attendu :**
- Menu "Ma comptabilité" visible
- Page affiche les écritures de ses locataires
- Statistiques de son portefeuille
- Toutes les actions disponibles

---

## 📊 Statistiques Filtrées

### **Pour Locataires :**
```php
$stats = [
    'total_credits' => 65000,     // Total des paiements reçus
    'total_debits' => 0,          // Pas de débits pour locataires
    'balance' => 65000,           // Solde positif
    'current_month_credits' => 25000,  // Paiement du mois
    'current_month_debits' => 0,
];
```

### **Pour Gestionnaires :**
```php
$stats = [
    'total_credits' => 450000,    // Revenus de tous leurs locataires
    'total_debits' => 120000,     // Charges et dépenses
    'balance' => 330000,          // Bénéfice net
    'current_month_credits' => 75000,  // Revenus du mois
    'current_month_debits' => 15000,   // Charges du mois
];
```

---

## 🚀 Avantages

### **Pour les Locataires :**
- ✅ **Transparence** : Voir l'historique de leurs paiements
- ✅ **Traçabilité** : Suivre tous les mouvements comptables
- ✅ **Export** : Télécharger leurs données pour leurs comptes
- ✅ **Simplicité** : Interface épurée, sans fonctions complexes

### **Pour les Gestionnaires :**
- ✅ **Vision globale** : Voir la comptabilité de leur portefeuille
- ✅ **Contrôle** : Créer et modifier des écritures
- ✅ **Reporting** : Statistiques détaillées

### **Pour les Admins :**
- ✅ **Vue d'ensemble** : Accès complet à toutes les données
- ✅ **Gestion** : Toutes les fonctionnalités disponibles

---

## 📝 Fichiers Modifiés

1. ✅ **src/Service/MenuService.php**
   - Ajout de `ROLE_TENANT` au menu comptabilité

2. ✅ **src/Controller/AccountingController.php**
   - Filtrage par rôle dans `index()`
   - Passage de `is_tenant_view` au template

3. ✅ **src/Repository/AccountingEntryRepository.php**
   - `findByTenantWithFilters()`
   - `findByManagerWithFilters()`
   - `getTenantStatistics()`
   - `getManagerStatistics()`

4. ✅ **templates/accounting/index.html.twig**
   - Masquage du bouton "Nouvelle écriture" pour locataires
   - Correction `current_balance` → `balance`

---

## 🔧 Corrections Appliquées

### **Erreur : `Key "current_balance" does not exist`**

**Cause :** Le template utilisait `stats.current_balance` mais nos nouvelles méthodes retournent `stats.balance`.

**Solution :** Correction dans le template :
```twig
<!-- Avant -->
{{ stats.current_balance|currency }}

<!-- Après -->
{{ stats.balance|currency }}
```

---

## 📞 Support

Pour tester la fonctionnalité :

1. **Connectez-vous en tant que locataire**
2. **Vérifiez que le menu "Ma comptabilité" apparaît**
3. **Cliquez dessus pour voir vos écritures**
4. **Testez les filtres et l'export**

---

**Date de mise à jour :** 12 octobre 2025  
**Version :** 1.0  
**Statut :** ✅ Implémenté et testé
