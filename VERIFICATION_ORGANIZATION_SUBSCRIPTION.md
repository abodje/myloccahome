# 🔍 Vérification : Organization ↔ Subscription

## ✅ Oui, Organization et Subscription sont bien LIÉES !

### **Relation Bidirectionnelle**

```
Organization ←→ Subscription
     1      ←→      N
```

**Organization** :
- Peut avoir PLUSIEURS subscriptions (historique)
- Mais UNE SEULE subscription ACTIVE à la fois

**Subscription** :
- Appartient à UNE SEULE organization

---

## 📊 Structure en Base de Données

### **Table `organization`**
```sql
CREATE TABLE organization (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    status VARCHAR(50),
    active_subscription_id INT,  -- ← Pointe vers subscription.id
    features JSON,
    settings JSON,
    created_at DATETIME,
    ...
    FOREIGN KEY (active_subscription_id) REFERENCES subscription(id)
);
```

### **Table `subscription`**
```sql
CREATE TABLE subscription (
    id INT PRIMARY KEY,
    organization_id INT NOT NULL,  -- ← Pointe vers organization.id
    plan_id INT NOT NULL,
    status VARCHAR(50),
    start_date DATE,
    end_date DATE,
    amount DECIMAL(10,2),
    currency VARCHAR(10),
    created_at DATETIME,
    ...
    FOREIGN KEY (organization_id) REFERENCES organization(id),
    FOREIGN KEY (plan_id) REFERENCES plan(id)
);
```

---

## 🎯 Exemple Réel : Parcours Client

### **Mois 1 : Inscription Freemium**
```sql
-- Organization créée
INSERT INTO organization (id, name, status, active_subscription_id)
VALUES (42, 'Agence Durand', 'ACTIVE', 1);

-- Subscription Freemium créée
INSERT INTO subscription (id, organization_id, plan_id, status, amount)
VALUES (1, 42, 4, 'ACTIVE', 0.00); -- Plan Freemium (id=4)
```

### **Mois 3 : Upgrade vers Professional**
```sql
-- Ancienne subscription marquée comme EXPIRED
UPDATE subscription SET status = 'EXPIRED' WHERE id = 1;

-- Nouvelle subscription Professional créée
INSERT INTO subscription (id, organization_id, plan_id, status, amount)
VALUES (2, 42, 2, 'ACTIVE', 24900.00); -- Plan Professional (id=2)

-- Organization pointe vers nouvelle subscription
UPDATE organization SET active_subscription_id = 2 WHERE id = 42;
```

### **Résultat Final**
```sql
-- Organization
SELECT * FROM organization WHERE id = 42;
-- id=42, name='Agence Durand', active_subscription_id=2

-- Subscription ACTIVE
SELECT * FROM subscription WHERE id = 2;
-- id=2, organization_id=42, plan_id=2, status='ACTIVE', amount=24900.00

-- Historique COMPLET
SELECT * FROM subscription WHERE organization_id = 42 ORDER BY created_at;
-- id=1, status='EXPIRED', plan_id=4 (Freemium)
-- id=2, status='ACTIVE', plan_id=2 (Professional)
```

---

## 🔗 Relations en Code PHP

### **1. Depuis Organization vers Subscription**

```php
$organization = $entityManager->find(Organization::class, 42);

// Récupérer la subscription ACTIVE
$activeSubscription = $organization->getActiveSubscription();
echo $activeSubscription->getPlan()->getName(); // "Professional"
echo $activeSubscription->getPrice(); // "24900.00"

// Récupérer TOUTES les subscriptions (historique)
$allSubscriptions = $organization->getSubscriptions();
echo count($allSubscriptions); // 2
foreach ($allSubscriptions as $sub) {
    echo "{$sub->getPlan()->getName()} - {$sub->getStatus()}\n";
}
// Output:
// Freemium - EXPIRED
// Professional - ACTIVE
```

### **2. Depuis Subscription vers Organization**

```php
$subscription = $entityManager->find(Subscription::class, 2);

// Récupérer l'organization propriétaire
$organization = $subscription->getOrganization();
echo $organization->getName(); // "Agence Durand"
echo $organization->getEmail(); // "contact@agence-durand.com"

// Vérifier si c'est la subscription active
$isActive = $organization->getActiveSubscription()->getId() === $subscription->getId();
echo $isActive ? "OUI" : "NON"; // "OUI"
```

---

## 🎨 Workflow Complet lors de l'Inscription

### **Dans RegistrationController::register()**

```php
// 1. Créer l'Organization
$organization = new Organization();
$organization->setName($orgName);
$organization->setStatus('TRIAL');
// ... autres champs

// 2. Créer la Subscription
$subscription = $subscriptionService->createSubscription(
    $organization,  // ← LIEN Organization → Subscription
    $plan,
    $billingCycle
);
// À l'intérieur de createSubscription():
// $subscription->setOrganization($organization); ← LIEN bidirectionnel

// 3. Activer pour Freemium
if ($plan->getSlug() === 'freemium') {
    $subscriptionService->activateSubscription($subscription);
    // À l'intérieur de activateSubscription():
    // $organization->setActiveSubscription($subscription); ← LIEN inverse
    // $organization->setStatus('ACTIVE');
}

// 4. Persister
$entityManager->persist($organization);
$entityManager->persist($subscription);
$entityManager->flush();
```

**Résultat en base** :
```
organization:
  id: 42
  name: "Agence Durand"
  active_subscription_id: 1  ← Pointe vers subscription
  status: "ACTIVE"

subscription:
  id: 1
  organization_id: 42  ← Pointe vers organization
  plan_id: 4 (Freemium)
  status: "ACTIVE"
```

---

## ✅ Vérification : Tout est bien cloisonné ?

### **Test 1 : Organization peut avoir plusieurs subscriptions ?**
```php
$organization = new Organization();
$subscription1 = new Subscription(); // Freemium
$subscription2 = new Subscription(); // Professional

$subscription1->setOrganization($organization);
$subscription2->setOrganization($organization);

$organization->getSubscriptions()->count(); // → 2 ✅
```

### **Test 2 : Une seule subscription active à la fois ?**
```php
$organization->setActiveSubscription($subscription1); // Freemium actif

// Upgrade vers Professional
$organization->setActiveSubscription($subscription2); // Professional actif

$organization->getActiveSubscription()->getPlan(); // → Professional ✅
```

### **Test 3 : Subscription appartient à UNE organisation ?**
```php
$subscription->getOrganization(); // → Organization #42 ✅
```

---

## 📋 Commandes de Vérification

### **Compter les organizations**
```bash
php bin/console doctrine:query:dql "SELECT COUNT(o.id) FROM App\Entity\Organization o"
```

### **Compter les subscriptions**
```bash
php bin/console doctrine:query:dql "SELECT COUNT(s.id) FROM App\Entity\Subscription s"
```

### **Lister organizations avec leur subscription active**
```bash
php bin/console doctrine:query:dql "
  SELECT o.id, o.name, s.status, p.name 
  FROM App\Entity\Organization o
  JOIN o.activeSubscription s
  JOIN s.plan p
"
```

### **Voir l'historique des subscriptions d'une organization**
```bash
php bin/console doctrine:query:dql "
  SELECT s.id, s.status, s.startDate, s.endDate, p.name
  FROM App\Entity\Subscription s
  JOIN s.plan p
  WHERE s.organization = 42
  ORDER BY s.createdAt DESC
"
```

---

## 🎯 Réponse à votre question

### **"Est-ce que l'organization est liée à la subscription ?"**

**OUI ! Absolument !** 

Les deux sont **intimement liées** :

1. ✅ **Chaque Organization DOIT avoir une Subscription** pour fonctionner
2. ✅ **Chaque Subscription appartient à UNE Organization**
3. ✅ **Une Organization garde l'historique de ses Subscriptions**
4. ✅ **Une Organization pointe vers sa Subscription ACTIVE**

**Analogie** :
- **Organization** = Votre entreprise cliente
- **Subscription** = Votre contrat d'abonnement avec MYLOCCA
- **Active Subscription** = Le contrat en cours
- **Subscriptions (collection)** = Tous vos anciens contrats + contrat actuel

**Sans Subscription, une Organization ne peut PAS utiliser MYLOCCA !**

---

## ✨ Résumé Visuel

```
┌─────────────────────────────────────────┐
│        AGENCE DURAND (Organization)     │
│  ┌───────────────────────────────────┐  │
│  │ Active Subscription: Professional │  │
│  └───────────────────────────────────┘  │
│                                          │
│  Historique des Subscriptions:          │
│  ├─ Jan 2025: Freemium (EXPIRED)        │
│  ├─ Feb 2025: Starter (EXPIRED)         │
│  └─ Jun 2025: Professional (ACTIVE) ←── │
│                                          │
│  Fonctionnalités actuelles:              │
│  ├─ Comptabilité avancée ✅             │
│  ├─ Paiements en ligne ✅               │
│  ├─ Notifications SMS ❌                │
│  └─ (selon plan Professional)           │
└─────────────────────────────────────────┘
```

**TOUT EST BIEN CLOISONNÉ ET LIEN ! ✅**


