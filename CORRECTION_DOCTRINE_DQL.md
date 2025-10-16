# 🔧 Correction Erreur Doctrine DQL - MYLOCCA

## ❌ Problème Identifié

**Erreur :** `[Syntax Error] line 0, col 41: Error: Expected known function, got 'YEAR'`

**Cause :** Utilisation de fonctions MySQL (`YEAR`, `MONTH`) dans les requêtes DQL de Doctrine, qui ne sont pas supportées directement.

---

## ✅ Corrections Appliquées

### **1. Méthode `generateFinancialReport`**

**Avant (❌ Incorrect) :**
```php
->where('YEAR(p.paymentDate) = :year')
->andWhere('MONTH(p.paymentDate) = :month')
->setParameter('year', $year)
->setParameter('month', $month)
```

**Après (✅ Correct) :**
```php
$startDate = new \DateTime("{$year}-{$month}-01");
$endDate = new \DateTime("{$year}-{$month}-" . $startDate->format('t'));

->where('p.paymentDate >= :startDate')
->andWhere('p.paymentDate <= :endDate')
->setParameter('startDate', $startDate)
->setParameter('endDate', $endDate)
```

### **2. Méthode `generateTaxDeclaration`**

**Avant (❌ Incorrect) :**
```php
->where('YEAR(p.paymentDate) = :year')
->setParameter('year', $year)
```

**Après (✅ Correct) :**
```php
$startDate = new \DateTime("{$year}-01-01");
$endDate = new \DateTime("{$year}-12-31");

->where('p.paymentDate >= :startDate')
->andWhere('p.paymentDate <= :endDate')
->setParameter('startDate', $startDate)
->setParameter('endDate', $endDate)
```

### **3. Méthode `generateAccountingReport`**

**Amélioration :** Création explicite des objets DateTime pour éviter les erreurs de parsing.

---

## 🧪 Test de Validation

### **1. Vider le Cache**
```powershell
php bin/console cache:clear
```

### **2. Tester un Export**
1. Aller sur `/admin/exports`
2. Cliquer sur "Excel" pour un rapport financier
3. Vérifier que le fichier se génère sans erreur

### **3. Vérifier les Logs**
```powershell
tail -f var/log/dev.log
```

---

## 📚 Explication Technique

### **Pourquoi cette Erreur ?**

**Doctrine DQL** (Doctrine Query Language) est un langage de requête qui :
- ✅ Supporte les fonctions Doctrine natives
- ❌ Ne supporte PAS les fonctions SQL spécifiques à la base de données
- ✅ Convertit automatiquement les comparaisons de dates

### **Fonctions MySQL Non Supportées**
```sql
-- ❌ Ces fonctions ne fonctionnent PAS en DQL
YEAR(date)
MONTH(date)
DAY(date)
DATE_FORMAT(date, format)
```

### **Solutions Recommandées**

#### **Option 1 : Comparaisons de Dates (Recommandée)**
```php
// ✅ Correct - Utilise les comparaisons de dates
$startDate = new \DateTime('2025-01-01');
$endDate = new \DateTime('2025-01-31');

$qb->where('p.paymentDate >= :startDate')
   ->andWhere('p.paymentDate <= :endDate')
   ->setParameter('startDate', $startDate)
   ->setParameter('endDate', $endDate);
```

#### **Option 2 : Requête SQL Native (Si nécessaire)**
```php
// Pour des cas complexes uniquement
$sql = "SELECT * FROM payment WHERE YEAR(payment_date) = ?";
$stmt = $connection->prepare($sql);
$stmt->execute([$year]);
```

#### **Option 3 : Fonctions Doctrine (Avancées)**
```php
// Pour des cas très spécifiques
use Doctrine\ORM\Query\Expr\Func;

$qb->addSelect(new Func('YEAR', 'p.paymentDate'));
```

---

## 🔍 Vérification Complète

### **Méthodes Corrigées**
- ✅ `generateFinancialReport()` - Filtrage par mois
- ✅ `generateTaxDeclaration()` - Filtrage par année  
- ✅ `generateAccountingReport()` - Filtrage par période

### **Méthodes Déjà Correctes**
- ✅ `generatePaymentsExport()` - Utilise déjà les comparaisons de dates
- ✅ `generateOverduePaymentsExport()` - Utilise déjà les comparaisons de dates
- ✅ `generateTenantsExport()` - Pas de filtrage par date
- ✅ `generatePropertiesExport()` - Pas de filtrage par date
- ✅ `generateLeasesExport()` - Pas de filtrage par date

---

## 🚀 Test Immédiat

### **1. Test Rapport Financier**
```bash
# URL de test
https://127.0.0.1:8000/admin/exports/rapports-financiers?format=excel&year=2025&month=10
```

### **2. Test Déclaration Fiscale**
```bash
# URL de test
https://127.0.0.1:8000/admin/exports/declaration-fiscale?format=excel&year=2025
```

### **3. Test Rapport Comptable**
```bash
# URL de test
https://127.0.0.1:8000/admin/exports/rapport-comptable?format=excel&start_date=2025-01-01&end_date=2025-12-31
```

---

## 💡 Bonnes Pratiques Doctrine

### **✅ À Faire**
```php
// Utiliser les comparaisons de dates
$qb->where('p.date >= :start')
   ->andWhere('p.date <= :end');

// Utiliser les fonctions Doctrine natives
$qb->orderBy('p.date', 'DESC');
$qb->groupBy('p.category');
$qb->having('COUNT(p.id) > 5');
```

### **❌ À Éviter**
```php
// Ne pas utiliser les fonctions SQL spécifiques
$qb->where('YEAR(p.date) = :year');        // ❌
$qb->where('MONTH(p.date) = :month');     // ❌
$qb->where('DATE_FORMAT(p.date, "%Y") = :year'); // ❌
```

---

## 🎯 Résultat Attendu

Après correction, tous les exports devraient fonctionner :

1. **✅ Rapport Financier** - Génération Excel sans erreur
2. **✅ Déclaration Fiscale** - Génération Excel sans erreur  
3. **✅ Rapport Comptable** - Génération Excel sans erreur
4. **✅ Tous les autres exports** - Déjà fonctionnels

---

**La correction est maintenant appliquée ! Testez les exports ! 🚀**
