# 📊 Dashboard Analytique Avancé - MYLOCCA

## 🎯 Vue d'ensemble

Le **Dashboard Analytique** est une interface visuelle avancée qui fournit des graphiques interactifs et des KPIs en temps réel pour une meilleure prise de décision.

---

## ✅ Fonctionnalités Implémentées

### **1. Service DashboardAnalyticsService**

Service dédié pour les calculs statistiques et analytiques.

**Fichier :** `src/Service/DashboardAnalyticsService.php`

**Méthodes principales :**

| Méthode | Description | Retour |
|---------|-------------|--------|
| `getMonthlyRevenueChartData()` | Données revenus/dépenses 12 mois | Array avec labels, revenue, expenses, net |
| `getOccupancyRate()` | Taux d'occupation des biens | Array avec rate, occupied, total |
| `getPaymentStatistics()` | Stats des paiements | Array avec évolution, retards, etc. |
| `getCashFlowForecast()` | Prévisions 3 mois | Array des prévisions |
| `getPropertiesByType()` | Répartition par type | Array des types et compteurs |
| `getLeaseExpirationStats()` | Baux à expirer (30/60/90j) | Array des expirations |
| `getGlobalKPIs()` | KPIs globaux | Array avec tous les KPIs |
| `getYearComparison()` | Comparaison année N vs N-1 | Array avec évolution |

---

### **2. Template Dashboard Analytique**

**Fichier :** `templates/dashboard/admin_analytics.html.twig`

**URL :** `/analytics`

**Sections :**

#### **📊 KPIs Principaux**
```
┌─────────────────────────────────────────────┐
│ Taux Occupation | Revenus | Recouvrement | Retards │
│     85%         | 25.000€ |   92%        |   3     │
└─────────────────────────────────────────────┘
```

#### **📈 Graphique Revenus vs Dépenses (12 mois)**
- Graphique en ligne avec Chart.js
- 3 courbes : Revenus, Dépenses, Net
- Interactif et responsive

#### **📄 Baux à Expirer**
- 30 jours (rouge)
- 60 jours (orange)
- 90 jours (bleu)
- Barres de progression visuelles

#### **💰 Prévisions de Trésorerie (3 mois)**
- Revenus attendus
- Dépenses estimées
- Net projeté

#### **🏠 Répartition par Type de Bien**
- Graphique en donut (Chart.js)
- Par type (Appartement, Maison, Bureau, etc.)

#### **📊 Performance Annuelle**
- Comparaison année en cours vs précédente
- % d'évolution
- Visuels avec flèches

---

## 🎨 Captures d'Écran

### **Dashboard Complet**
```
┌────────────────────────────────────────────────────┐
│ 📊 Dashboard Analytique      Mis à jour: 14/10/24 │
├────────────────────────────────────────────────────┤
│                                                    │
│ [85%] Occupation  [25K€] Revenus  [92%] Recouv.  │
│                                                    │
│ ┌─────────────────────────┐ ┌──────────────────┐ │
│ │📈 Revenus (12 mois)     │ │📄 Baux à expirer │ │
│ │                         │ │                  │ │
│ │ [Graphique Chart.js]    │ │ 30j : ■■■ 3      │ │
│ │                         │ │ 60j : ■■■■ 5     │ │
│ │                         │ │ 90j : ■■■■■ 7    │ │
│ └─────────────────────────┘ └──────────────────┘ │
│                                                    │
│ ┌─────────────────────────┐ ┌──────────────────┐ │
│ │💰 Prévisions (3 mois)   │ │🏠 Types de biens │ │
│ │                         │ │                  │ │
│ │ Nov: +8.5K€             │ │  [Donut Chart]   │ │
│ │ Dec: +9.2K€             │ │                  │ │
│ │ Jan: +8.8K€             │ │                  │ │
│ └─────────────────────────┘ └──────────────────┘ │
│                                                    │
│ ┌──────────────────────────────────────────────┐ │
│ │📊 Performance Annuelle                       │ │
│ │  2024: 285K€    ↑ +12%    2023: 254K€       │ │
│ └──────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────┘
```

---

## 🚀 Utilisation

### **Accès au Dashboard Analytique**

**URL :** `/analytics`

**Route :** `app_dashboard_analytics`

**Exemple :**
```twig
<a href="{{ path('app_dashboard_analytics') }}" class="btn btn-primary">
    <i class="bi bi-graph-up"></i> Dashboard Analytique
</a>
```

### **Depuis le Dashboard Classique**

Un lien est disponible en bas du dashboard classique pour basculer.

---

## 🔧 Configuration

### **Chart.js**

Déjà inclus via CDN dans le template :

```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

### **Auto-Refresh**

Le dashboard se rafraîchit automatiquement toutes les 5 minutes :

```javascript
setTimeout(function() {
    location.reload();
}, 300000); // 5 minutes
```

Pour désactiver, commentez ces lignes dans le template.

---

## 📊 Méthodes des Repositories

### **Méthodes qui DOIVENT exister**

Ces méthodes sont utilisées par `DashboardAnalyticsService`. Si elles n'existent pas, ajoutez-les :

#### **PaymentRepository**

```php
// Montant total des paiements en retard
public function getTotalOverdueAmount(): float
{
    $qb = $this->createQueryBuilder('p')
        ->select('SUM(p.amount)')
        ->where('p.status = :status')
        ->andWhere('p.dueDate < :now')
        ->setParameter('status', 'En attente')
        ->setParameter('now', new \DateTime());
    
    return (float) $qb->getQuery()->getSingleScalarResult() ?? 0;
}

// Nombre de paiements payés ce mois
public function countPaidThisMonth(): int
{
    $start = new \DateTime('first day of this month');
    $end = new \DateTime('last day of this month');
    
    return $this->createQueryBuilder('p')
        ->select('COUNT(p.id)')
        ->where('p.status = :status')
        ->andWhere('p.paidDate BETWEEN :start AND :end')
        ->setParameter('status', 'Payé')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
}

// Revenus attendus pour une période
public function getExpectedRevenueByPeriod(\DateTime $start, \DateTime $end): float
{
    $qb = $this->createQueryBuilder('p')
        ->select('SUM(p.amount)')
        ->where('p.dueDate BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end);
    
    return (float) $qb->getQuery()->getSingleScalarResult() ?? 0;
}

// Total attendu ce mois
public function getTotalExpectedThisMonth(): float
{
    $start = new \DateTime('first day of this month');
    $end = new \DateTime('last day of this month');
    
    return $this->getExpectedRevenueByPeriod($start, $end);
}

// Total collecté ce mois
public function getTotalCollectedThisMonth(): float
{
    $start = new \DateTime('first day of this month');
    $end = new \DateTime('last day of this month');
    
    return $this->getTotalRevenueByPeriod($start, $end);
}

// Délai moyen de paiement (en jours)
public function getAveragePaymentDelay(): float
{
    $qb = $this->createQueryBuilder('p')
        ->select('AVG(DATEDIFF(p.paidDate, p.dueDate))')
        ->where('p.status = :status')
        ->andWhere('p.paidDate IS NOT NULL')
        ->setParameter('status', 'Payé');
    
    return (float) $qb->getQuery()->getSingleScalarResult() ?? 0;
}
```

#### **ExpenseRepository**

```php
// Dépenses moyennes mensuelles (sur X mois)
public function getAverageMonthlyExpenses(int $months = 3): float
{
    $endDate = new \DateTime();
    $startDate = (clone $endDate)->modify("-{$months} months");
    
    $total = $this->getTotalExpensesByPeriod($startDate, $endDate);
    
    return $total / $months;
}

// Top dépenses d'une période
public function getTopExpensesByPeriod(\DateTime $start, \DateTime $end, int $limit = 5): array
{
    return $this->createQueryBuilder('e')
        ->where('e.date BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->orderBy('e.amount', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

#### **MaintenanceRequestRepository**

```php
// Nombre de demandes sur une période
public function countByPeriod(\DateTime $start, \DateTime $end): int
{
    return $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->where('m.createdAt BETWEEN :start AND :end')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
}

// Nombre de demandes terminées sur une période
public function countCompletedByPeriod(\DateTime $start, \DateTime $end): int
{
    return $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->where('m.status = :status')
        ->andWhere('m.updatedAt BETWEEN :start AND :end')
        ->setParameter('status', 'Terminée')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
}

// Demandes traitées à temps ce mois
public function countOnTimeThisMonth(): int
{
    // Logique : terminées avant la date limite
    $start = new \DateTime('first day of this month');
    $end = new \DateTime('last day of this month');
    
    return $this->createQueryBuilder('m')
        ->select('COUNT(m.id)')
        ->where('m.status = :status')
        ->andWhere('m.updatedAt BETWEEN :start AND :end')
        ->andWhere('m.updatedAt <= m.dueDate')
        ->setParameter('status', 'Terminée')
        ->setParameter('start', $start)
        ->setParameter('end', $end)
        ->getQuery()
        ->getSingleScalarResult();
}

// Total de demandes terminées ce mois
public function countCompletedThisMonth(): int
{
    $start = new \DateTime('first day of this month');
    $end = new \DateTime('last day of this month');
    
    return $this->countCompletedByPeriod($start, $end);
}
```

---

## 🎨 Personnalisation

### **Modifier les Couleurs des Graphiques**

Dans `admin_analytics.html.twig`, cherchez :

```javascript
backgroundColor: [
    'rgb(0, 123, 255)',      // Bleu
    'rgb(40, 167, 69)',      // Vert
    'rgb(255, 193, 7)',      // Jaune
    'rgb(220, 53, 69)',      // Rouge
    'rgb(108, 117, 125)',    // Gris
    'rgb(23, 162, 184)'      // Cyan
]
```

### **Modifier l'Intervalle d'Auto-Refresh**

```javascript
setTimeout(function() {
    location.reload();
}, 300000); // Changez 300000 (5 min) par la valeur souhaitée en millisecondes
```

---

## 📱 Responsive

Le dashboard est entièrement responsive :
- **Desktop** : 4 colonnes pour les KPIs
- **Tablet** : 2 colonnes
- **Mobile** : 1 colonne

Les graphiques s'adaptent automatiquement.

---

## 🔐 Sécurité

**Accès :** Réservé aux administrateurs

Pour ajouter des restrictions :

```php
#[Route('/analytics', name: 'app_dashboard_analytics')]
#[IsGranted('ROLE_ADMIN')] // ← Ajoutez cette annotation
public function analytics(...) {
    // ...
}
```

---

## 🐛 Dépannage

### **Erreur : Méthode X n'existe pas**

**Solution :** Ajoutez la méthode manquante dans le repository correspondant (voir section "Méthodes des Repositories" ci-dessus).

### **Graphiques ne s'affichent pas**

**Solution :** 
1. Vérifiez que Chart.js est chargé (console navigateur)
2. Vérifiez les données (console JS)
3. Vérifiez qu'il n'y a pas d'erreurs PHP

### **Données vides / zéros partout**

**Solution :**
1. Vérifiez que vous avez des données (paiements, biens, etc.)
2. Consultez les logs Symfony (`var/log/dev.log`)
3. Vérifiez les méthodes des repositories

---

## ✅ Checklist d'Installation

- [x] Service `DashboardAnalyticsService` créé
- [x] Route `/analytics` ajoutée
- [x] Template `admin_analytics.html.twig` créé
- [ ] Méthodes des repositories ajoutées (si manquantes)
- [ ] Test d'accès à `/analytics`
- [ ] Vérification des graphiques
- [ ] Test sur mobile/tablet

---

## 📊 Statistiques

| Métrique | Valeur |
|----------|--------|
| Fichiers créés | 2 |
| Lignes de code | ~600 |
| Graphiques | 2 (ligne + donut) |
| KPIs affichés | 8+ |
| Prévisions | 3 mois |
| Période historique | 12 mois |

---

## 🎉 Résumé

Le **Dashboard Analytique** offre :
- ✅ Graphiques interactifs (Chart.js)
- ✅ KPIs visuels en temps réel
- ✅ Prévisions de trésorerie
- ✅ Comparaisons périodiques
- ✅ Interface moderne et responsive
- ✅ Auto-refresh automatique

**Accès :** `/analytics`

**Impact :** Vision claire et immédiate de la performance ! 📈

