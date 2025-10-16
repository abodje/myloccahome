# 📅 Calendrier de Gestion - MYLOCCA

## 🎯 Vue d'ensemble

Le **Calendrier de Gestion** est une interface visuelle interactive qui affiche tous les événements importants de votre activité locative sur un calendrier mensuel/hebdomadaire/quotidien.

---

## ✅ Fonctionnalités Implémentées

### **1. Contrôleur CalendarController**

**Fichier :** `src/Controller/CalendarController.php`

**Routes :**
- `GET /calendrier` - Affiche le calendrier
- `GET /calendrier/events` - API JSON pour les événements

### **2. Types d'Événements Affichés**

#### **💰 Paiements**
- **Couleur :** 
  - 🟢 Vert (#28a745) - Payé
  - 🟡 Jaune (#ffc107) - En attente
  - 🔴 Rouge (#dc3545) - En retard

- **Informations affichées :**
  - Montant et devise
  - Locataire
  - Bien
  - Statut

#### **📄 Expirations de Baux**
- **Couleur :**
  - 🔴 Rouge - Expire dans 30 jours
  - 🟡 Jaune - Expire dans 60 jours
  - 🔵 Bleu (#17a2b8) - Expire dans 90+ jours

- **Informations affichées :**
  - Locataire
  - Bien
  - Date début/fin
  - Durée

#### **🔧 Maintenances**
- **Couleur :**
  - 🔴 Rouge - Nouvelle (urgent)
  - 🟡 Jaune - En cours
  - 🟢 Vert - Terminée

- **Informations affichées :**
  - Type/Titre
  - Bien
  - Description
  - Statut

---

## 🎨 Interface Utilisateur

### **Vues Disponibles**

| Vue | Icône | Description |
|-----|-------|-------------|
| **Mois** | 📅 | Vue calendrier mensuel (par défaut) |
| **Semaine** | 📆 | Vue hebdomadaire avec heures |
| **Jour** | 📋 | Vue détaillée d'une journée |
| **Agenda** | 📃 | Liste chronologique |

### **Statistiques en Haut**

```
┌──────────────────────────────────────────────────┐
│ [✓ 12]      [⏰ 5]      [📄 3]      [🔧 2]      │
│ Payés     En attente   Baux      Maintenances   │
└──────────────────────────────────────────────────┘
```

**Mise à jour :** Automatique lors du changement de mois

### **Filtres Dynamiques**

```
Filtrer par Type:
[💰 Paiements] [📄 Baux] [🔧 Maintenances]
   (actif)      (actif)      (actif)
```

**Fonctionnement :** 
- Cliquez pour activer/désactiver
- Le calendrier se met à jour instantanément
- Les filtres sont visuels (actif = badge agrandi)

### **Légende des Couleurs**

```
🟢 Vert    = Payé / Terminé
🟡 Jaune   = En attente / En cours
🔴 Rouge   = En retard / Urgent
🔵 Bleu    = Expiration bail
```

---

## 🚀 Utilisation

### **Accès au Calendrier**

**URL :** `http://votre-domaine.com/calendrier`

**Route :** `app_calendar_index`

**Exemple lien :**
```twig
<a href="{{ path('app_calendar_index') }}" class="btn btn-primary">
    <i class="bi bi-calendar"></i> Calendrier
</a>
```

### **Navigation**

- **◀ Précédent** : Mois/semaine/jour précédent
- **Aujourd'hui** : Revenir à la date actuelle
- **Suivant ▶** : Mois/semaine/jour suivant

### **Changer de Vue**

Cliquez sur les boutons en haut à droite :
- **Mois** : Vue mensuelle
- **Semaine** : Vue hebdomadaire
- **Jour** : Vue journalière
- **Agenda** : Liste des événements

### **Cliquer sur un Événement**

```
Clic sur événement
    ↓
Modal popup s'ouvre
    ↓
Affiche détails complets
    ↓
[Fermer] ou [Voir détails]
```

---

## 🔧 Personnalisation

### **Modifier les Couleurs**

Dans `CalendarController.php`, cherchez :

```php
$color = match($payment->getStatus()) {
    'Payé' => '#28a745',        // ← Changez ici
    'En attente' => '#ffc107',
    'En retard' => '#dc3545',
    default => '#6c757d'
};
```

### **Modifier les Icônes**

```php
$icon = match($payment->getStatus()) {
    'Payé' => '✓',          // ← Changez ici
    'En attente' => '⏰',
    'En retard' => '⚠️',
    default => '💰'
};
```

### **Ajouter un Nouveau Type d'Événement**

1. Créez une méthode `getXXXForCalendar()` dans le contrôleur
2. Appelez-la dans `events()`
3. Ajoutez un filtre dans le template
4. Ajoutez la légende

**Exemple pour Expenses (Dépenses) :**

```php
// Dans events()
if (!isset($filters['types']) || in_array('expenses', $filters['types'])) {
    $expenses = $this->getExpensesForCalendar($expenseRepo, $startDate, $endDate, $user);
    $events = array_merge($events, $expenses);
}

// Nouvelle méthode
private function getExpensesForCalendar($expenseRepo, $startDate, $endDate, $user): array
{
    $expenses = [];
    $allExpenses = $expenseRepo->findByPeriod($startDate, $endDate);
    
    foreach ($allExpenses as $expense) {
        $expenses[] = [
            'id' => 'expense-' . $expense->getId(),
            'title' => '💸 ' . $expense->getAmount() . ' - ' . $expense->getCategory(),
            'start' => $expense->getDate()->format('Y-m-d'),
            'backgroundColor' => '#dc3545',
            'borderColor' => '#dc3545',
            'extendedProps' => [
                'type' => 'expense',
                'amount' => $expense->getAmount(),
                'category' => $expense->getCategory()
            ]
        ];
    }
    
    return $expenses;
}
```

---

## 📱 Responsive

Le calendrier s'adapte automatiquement :

- **Desktop (>992px)** : Vue complète avec sidebar
- **Tablet (768-992px)** : Vue optimisée
- **Mobile (<768px)** : Vue liste par défaut

### **Mobile : Vue Optimisée**

Sur mobile, FullCalendar passe automatiquement en :
- Vue liste (plus lisible)
- Boutons plus grands
- Navigation simplifiée

---

## 🎯 Cas d'Usage

### **Cas 1 : Planifier les Collectes de Loyer**

**Besoin :** Voir tous les paiements à collecter ce mois

**Solution :**
1. Ouvrir le calendrier
2. Vue "Mois"
3. Filtrer uniquement "Paiements"
4. Les événements jaunes 🟡 = à collecter
5. Cliquer pour voir détails

---

### **Cas 2 : Anticiper les Renouvellements de Baux**

**Besoin :** Identifier les baux qui expirent bientôt

**Solution :**
1. Ouvrir le calendrier
2. Filtrer uniquement "Baux"
3. Les événements rouges 🔴 = expirent dans 30j
4. Les événements jaunes 🟡 = expirent dans 60j
5. Contacter les locataires pour renouvellement

---

### **Cas 3 : Organiser les Maintenances**

**Besoin :** Planifier les interventions du mois

**Solution :**
1. Ouvrir le calendrier
2. Vue "Semaine"
3. Filtrer uniquement "Maintenances"
4. Les événements rouges 🔴 = urgentes
5. Organiser les interventions

---

### **Cas 4 : Vue d'Ensemble Mensuelle**

**Besoin :** Voir toute l'activité du mois

**Solution :**
1. Vue "Mois" avec tous les filtres actifs
2. Aperçu complet des événements
3. Statistiques en haut pour un résumé
4. Cliquer sur un jour pour voir détails

---

## 🔐 Filtrage Multi-Tenant

Le calendrier respecte automatiquement les permissions :

### **ROLE_ADMIN**
- ✅ Voit tous les événements de toutes les organisations
- ✅ Accès complet

### **ROLE_MANAGER**
- ✅ Voit uniquement ses propriétés et ses locataires
- ✅ Filtrage automatique par owner

### **ROLE_TENANT**
- ✅ Voit uniquement ses propres paiements et maintenances
- ✅ Filtrage automatique par tenant

---

## 📊 Exemple Visuel

### **Vue Mois**
```
        Novembre 2024          [Mois][Semaine][Jour][Agenda]
─────────────────────────────────────────────────────────
Lu  Ma  Me  Je  Ve  Sa  Di
                    1   2   3
    🟡💰 800€
 4  5   6   7   8   9  10
🟢✓ 750€        🟡⏰ 900€
11 12  13  14  15  16  17
        🔧🔴            🟡⏰ 850€
18 19  20  21  22  23  24
🟢✓ 700€
25 26  27  28  29  30
            📄🔴 Bail expire
```

### **Vue Semaine**
```
        Lundi 11 Nov - Dimanche 17 Nov
─────────────────────────────────────────
08:00
09:00
10:00  🔧 Maintenance - Appt 23A
11:00
12:00
...
```

### **Vue Agenda**
```
Événements à venir
─────────────────────────────────────────
14 Nov 2024
  🟡 Paiement en attente - 900€ - Jean Dupont

15 Nov 2024
  🔧 Maintenance en cours - Appt 45B

18 Nov 2024
  🟢 Paiement reçu - 700€ - Marie Martin
```

---

## 💡 Astuces

### **Navigation Rapide**

- **Clic sur un jour** : Ouvre la vue jour
- **Bouton "Aujourd'hui"** : Revient à la date actuelle
- **Flèches** : Navigue dans le temps

### **Filtrage Efficace**

- **Paiements seulement** : Désactiver Baux et Maintenances
- **Urgences** : Regarder les événements rouges 🔴
- **Planning** : Vue semaine pour détails horaires

### **Modal de Détails**

- Affiche toutes les informations
- Bouton "Voir détails" pour accéder à l'entité complète
- Fermeture rapide (Esc ou clic hors modal)

---

## 🔄 Mises à Jour

### **Auto-Refresh**

Le calendrier recharge les événements automatiquement lors :
- Changement de mois
- Changement de vue
- Activation/désactivation d'un filtre

### **Données en Temps Réel**

Les événements sont chargés dynamiquement via l'API :

```
GET /calendrier/events?start=2024-11-01&end=2024-11-30&filters[types][]=payments
```

**Réponse JSON :**
```json
[
  {
    "id": "payment-123",
    "title": "✓ 800 EUR",
    "start": "2024-11-14",
    "backgroundColor": "#28a745",
    "extendedProps": {
      "type": "payment",
      "status": "Payé",
      "amount": 800,
      "tenant": "Jean Dupont",
      "property": "23 Rue de la Paix"
    }
  }
]
```

---

## 🎨 Personnalisation Avancée

### **Changer la Vue Par Défaut**

Dans `templates/calendar/index.html.twig` :

```javascript
const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'timeGridWeek',  // ← Changez 'dayGridMonth' en 'timeGridWeek'
    // ...
});
```

**Options disponibles :**
- `dayGridMonth` - Vue mois (par défaut)
- `timeGridWeek` - Vue semaine
- `timeGridDay` - Vue jour
- `listMonth` - Liste mensuelle

### **Modifier le Nombre d'Événements par Jour**

```javascript
dayMaxEvents: 3,  // ← Changez le nombre
```

### **Ajouter Plus de Boutons**

```javascript
headerToolbar: {
    left: 'prev,next today',
    center: 'title',
    right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth,listYear'  // ← Ajoutez listYear
}
```

---

## 🔍 Filtrage Multi-Tenant

Le calendrier applique automatiquement les filtres selon le rôle :

### **Admin**
```sql
SELECT * FROM payment  -- Tous les paiements
SELECT * FROM lease    -- Tous les baux
SELECT * FROM maintenance_request  -- Toutes les maintenances
```

### **Manager**
```sql
SELECT * FROM payment WHERE lease_id IN (
    SELECT id FROM lease WHERE property_id IN (
        SELECT id FROM property WHERE owner_id = ?
    )
)
```

### **Tenant**
```sql
SELECT * FROM payment WHERE lease_id IN (
    SELECT id FROM lease WHERE tenant_id = ?
)
```

---

## 📊 Statistiques Dynamiques

Les cartes en haut du calendrier se mettent à jour automatiquement :

```javascript
function updateStats(events) {
    let paid = 0, pending = 0, leases = 0, maintenance = 0;

    events.forEach(event => {
        if (event.extendedProps.type === 'payment') {
            if (event.extendedProps.status === 'Payé') paid++;
            else if (event.extendedProps.status === 'En attente') pending++;
        } else if (event.extendedProps.type === 'lease') {
            leases++;
        } else if (event.extendedProps.type === 'maintenance') {
            maintenance++;
        }
    });

    // Mise à jour affichage
}
```

---

## 🎯 Exemples de Modal

### **Modal Paiement**
```
┌─────────────────────────────────────┐
│ ✓ 800 EUR               [✕]        │
├─────────────────────────────────────┤
│                                     │
│ Type:      [Paiement]               │
│ Statut:    [Payé]                   │
│ Montant:   800 EUR                  │
│ Locataire: Jean Dupont              │
│ Bien:      23 Rue de la Paix        │
│ Date:      2024-11-14               │
│                                     │
│           [Fermer] [Voir détails]   │
└─────────────────────────────────────┘
```

### **Modal Bail**
```
┌─────────────────────────────────────┐
│ 📄 Expiration bail - Jean Dupont [✕]│
├─────────────────────────────────────┤
│                                     │
│ Type:      [Expiration Bail]        │
│ Locataire: Jean Dupont              │
│ Bien:      23 Rue de la Paix        │
│ Début:     01/01/2023               │
│ Fin:       31/12/2024               │
│                                     │
│           [Fermer] [Voir détails]   │
└─────────────────────────────────────┘
```

---

## 🔌 API Events

### **Endpoint**

```
GET /calendrier/events
```

### **Paramètres**

| Paramètre | Type | Description | Exemple |
|-----------|------|-------------|---------|
| `start` | string (ISO) | Date début | 2024-11-01 |
| `end` | string (ISO) | Date fin | 2024-11-30 |
| `filters[types][]` | array | Types à inclure | payments, leases |

### **Exemple de Requête**

```
GET /calendrier/events?start=2024-11-01&end=2024-11-30&filters[types][]=payments&filters[types][]=leases
```

### **Exemple de Réponse**

```json
[
  {
    "id": "payment-123",
    "title": "✓ 800 EUR",
    "start": "2024-11-14",
    "backgroundColor": "#28a745",
    "borderColor": "#28a745",
    "extendedProps": {
      "type": "payment",
      "status": "Payé",
      "amount": 800,
      "tenant": "Jean Dupont",
      "property": "23 Rue de la Paix",
      "paymentId": 123
    }
  },
  {
    "id": "lease-45",
    "title": "📄 Expiration bail - Marie Martin",
    "start": "2024-11-30",
    "backgroundColor": "#dc3545",
    "borderColor": "#dc3545",
    "extendedProps": {
      "type": "lease",
      "tenant": "Marie Martin",
      "property": "45 Avenue de la République",
      "startDate": "01/12/2022",
      "endDate": "30/11/2024",
      "leaseId": 45
    }
  }
]
```

---

## 🔧 Configuration FullCalendar

### **Options Principales**

```javascript
{
    locale: 'fr',                    // Langue française
    initialView: 'dayGridMonth',     // Vue par défaut
    height: 'auto',                  // Hauteur automatique
    navLinks: true,                  // Clic sur jour = navigation
    editable: false,                 // Pas de drag & drop
    dayMaxEvents: 3,                 // Max 3 événements/jour
    displayEventTime: false,         // Pas d'heure affichée
    
    // Boutons
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
    },
    
    // Textes
    buttonText: {
        today: 'Aujourd\'hui',
        month: 'Mois',
        week: 'Semaine',
        day: 'Jour',
        list: 'Agenda'
    }
}
```

---

## 🚀 Évolutions Futures

### **Phase 2**
- [ ] Drag & drop pour déplacer événements
- [ ] Création d'événements directement dans le calendrier
- [ ] Export iCal / Google Calendar
- [ ] Synchronisation avec calendriers externes

### **Phase 3**
- [ ] Rappels configurables
- [ ] Vue planning pour maintenances
- [ ] Réservation de créneaux
- [ ] Partage de calendrier

---

## 🧪 Tests Recommandés

### **Test 1 : Affichage**
```
✓ Accéder à /calendrier
✓ Le calendrier se charge
✓ Les événements s'affichent
✓ Les couleurs sont correctes
```

### **Test 2 : Navigation**
```
✓ Cliquer sur "Suivant" → Mois suivant
✓ Cliquer sur "Aujourd'hui" → Retour date actuelle
✓ Changer de vue (Mois/Semaine/Jour/Agenda)
```

### **Test 3 : Filtres**
```
✓ Désactiver "Paiements" → Paiements disparaissent
✓ Réactiver "Paiements" → Paiements réapparaissent
✓ Combiner filtres
```

### **Test 4 : Modal**
```
✓ Cliquer sur événement → Modal s'ouvre
✓ Détails affichés correctement
✓ Bouton "Voir détails" fonctionne
✓ Fermeture modal (Esc ou bouton)
```

### **Test 5 : Responsive**
```
✓ Tester sur desktop (1920px)
✓ Tester sur tablet (768px)
✓ Tester sur mobile (375px)
✓ Navigation fluide sur tous appareils
```

---

## ✅ Checklist d'Installation

- [x] Contrôleur CalendarController créé
- [x] Route `/calendrier` ajoutée
- [x] Route API `/calendrier/events` ajoutée
- [x] Template avec FullCalendar.js créé
- [x] Filtres dynamiques implémentés
- [x] Modal de détails créée
- [x] Statistiques en temps réel
- [x] Filtrage multi-tenant
- [x] Responsive design
- [x] Documentation complète
- [ ] Lien ajouté dans le menu principal
- [ ] Tests effectués
- [ ] Formation utilisateurs

---

## 📋 Intégration au Menu

Ajoutez dans votre navigation principale :

```twig
<li class="nav-item">
    <a href="{{ path('app_calendar_index') }}" class="nav-link">
        <i class="bi bi-calendar3"></i>
        <span>Calendrier</span>
    </a>
</li>
```

Ou dans le dashboard :

```twig
<div class="col-md-3">
    <a href="{{ path('app_calendar_index') }}" class="btn btn-outline-primary w-100">
        <i class="bi bi-calendar3 d-block mb-2" style="font-size: 2rem;"></i>
        Calendrier
    </a>
</div>
```

---

## 🎓 Résumé

Le **Calendrier de Gestion** offre :
- ✅ Interface visuelle intuitive
- ✅ 3 types d'événements (Paiements, Baux, Maintenances)
- ✅ 4 vues (Mois, Semaine, Jour, Agenda)
- ✅ Filtres dynamiques
- ✅ Statistiques en temps réel
- ✅ Modal de détails
- ✅ Responsive (mobile/tablet/desktop)
- ✅ Filtrage multi-tenant automatique
- ✅ Légende des couleurs
- ✅ FullCalendar.js 6.1.9

**Accès :** `/calendrier`

**Impact :** Organisation et planification optimales ! 📅✨

