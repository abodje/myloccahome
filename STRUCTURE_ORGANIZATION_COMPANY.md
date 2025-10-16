# 🏢 Structure Organization → Company → Gestionnaires

## 🎯 Vision Globale

```
Organization (Holding/Groupe)
  │
  ├── Subscription (Abonnement SaaS)
  │
  ├── Company 1 (Société/Agence A)
  │   ├── Manager 1
  │   ├── Manager 2
  │   ├── Properties (Biens immobiliers)
  │   ├── Tenants (Locataires)
  │   └── Leases (Baux)
  │
  └── Company 2 (Société/Agence B)
      ├── Manager 3
      ├── Properties
      ├── Tenants
      └── Leases
```

---

## 📊 Hiérarchie Complète

### **Niveau 1 : Organization**
**C'est quoi ?** Le compte principal qui souscrit à MYLOCCA

**Exemples** :
- Groupe Immobilier Durand
- Holding ABC
- Mon Entreprise Immobilière

**Contient** :
- Informations globales (nom, email, téléphone)
- Abonnement SaaS actif
- Features disponibles selon le plan
- Plusieurs sociétés (companies)

---

### **Niveau 2 : Company (Société)**
**C'est quoi ?** Une filiale, agence, ou division de l'organization

**Exemples** :
- Agence Durand Paris
- Agence Durand Lyon
- Filiale Nord
- Filiale Sud

**Contient** :
- Informations spécifiques (adresse, SIRET, etc.)
- Gestionnaires assignés
- Propriétés gérées
- Locataires
- Baux

---

### **Niveau 3 : Managers (Gestionnaires)**
**C'est quoi ?** Les employés qui gèrent une société spécifique

**Exemples** :
- Jean Dupont (Gestionnaire Agence Paris)
- Marie Martin (Gestionnaire Agence Lyon)

**Peut** :
- Gérer les biens de SA société uniquement
- Créer des locataires pour SA société
- Voir les paiements de SA société

---

## 🔗 Relations en Base de Données

### **Table `organization`**
```sql
CREATE TABLE organization (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    status VARCHAR(50),
    active_subscription_id INT,
    features JSON,
    settings JSON
);
```

### **Table `company` (NOUVELLE)**
```sql
CREATE TABLE company (
    id INT PRIMARY KEY,
    organization_id INT NOT NULL,  -- Appartient à Organization
    name VARCHAR(255),
    legal_name VARCHAR(255),       -- Raison sociale
    registration_number VARCHAR(100), -- SIRET/SIREN
    tax_number VARCHAR(255),
    address VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    logo VARCHAR(255),
    status VARCHAR(50),
    is_headquarter BOOLEAN,        -- Siège social ?
    created_at DATETIME,
    FOREIGN KEY (organization_id) REFERENCES organization(id)
);
```

### **Table `user` (Modifiée)**
```sql
ALTER TABLE user ADD company_id INT;
ALTER TABLE user ADD CONSTRAINT FK_user_company 
    FOREIGN KEY (company_id) REFERENCES company(id);
```

### **Tables `property`, `tenant`, `lease` (Modifiées)**
```sql
ALTER TABLE property ADD company_id INT;
ALTER TABLE tenant ADD company_id INT;
ALTER TABLE lease ADD company_id INT;
```

---

## 🎯 Workflow d'Inscription Complet

### **Étape 1 : Utilisateur s'inscrit**
```
Formulaire:
- Nom de l'entreprise: "Groupe Immobilier Durand"
- Email: contact@durand-immo.fr
- Téléphone: 01 23 45 67 89
- Utilisateur: Jean Durand
- Plan choisi: Professional
```

### **Étape 2 : Système crée automatiquement**
```php
// 1. Organization
$organization = new Organization();
$organization->setName("Groupe Immobilier Durand");
$organization->setEmail("contact@durand-immo.fr");
$organization->setFeatures($plan->getFeatures());
$organization->setStatus('TRIAL');

// 2. Company (Siège social par défaut)
$company = new Company();
$company->setName("Groupe Immobilier Durand");
$company->setOrganization($organization);
$company->setIsHeadquarter(true); // ✅ C'est le siège
$company->setStatus('ACTIVE');

// 3. User Admin
$user = new User();
$user->setEmail("jean@durand-immo.fr");
$user->setRoles(['ROLE_ADMIN']);
$user->setOrganization($organization);
// PAS de company pour l'admin (il voit TOUT)

// 4. Subscription
$subscription = new Subscription();
$subscription->setOrganization($organization);
$subscription->setPlan($plan);
```

### **Étape 3 : Admin peut créer plus de sociétés**
```
Admin se connecte → Menu "Sociétés" → "Nouvelle Société"

Formulaire:
- Nom: "Agence Paris 15ème"
- Adresse: 123 rue de Vaugirard, 75015 Paris
- SIRET: 12345678900012
- Manager: (Créer nouveau ou assigner existant)
```

### **Étape 4 : Admin crée des gestionnaires**
```
Admin → "Utilisateurs" → "Nouveau Gestionnaire"

Formulaire:
- Nom: Marie Martin
- Email: marie@durand-immo.fr
- Rôle: ROLE_MANAGER
- Société assignée: Agence Paris 15ème ← IMPORTANT
```

---

## 🔐 Droits d'Accès par Rôle

### **ROLE_ADMIN (Chef d'organisation)**
```
✅ Voit TOUTES les sociétés
✅ Voit TOUTES les propriétés
✅ Voit TOUS les locataires
✅ Peut créer/modifier des sociétés
✅ Peut créer/assigner des gestionnaires
✅ Gère l'abonnement
```

### **ROLE_MANAGER (Gestionnaire de société)**
```
✅ Voit SA société uniquement
✅ Voit les propriétés de SA société
✅ Voit les locataires de SA société
❌ Ne voit PAS les autres sociétés
❌ Ne peut PAS créer de sociétés
❌ Ne peut PAS gérer l'abonnement
```

### **ROLE_TENANT (Locataire)**
```
✅ Voit ses propres données
❌ Ne voit rien d'autre
```

---

## 💡 Cas d'Usage Réels

### **Cas 1 : Petite Agence (1 société)**
```
Organization: "Mon Agence Immo"
  └── Company: "Mon Agence Immo" (siège)
       ├── Admin: Pierre (voit tout)
       ├── 5 propriétés
       └── 12 locataires
```

### **Cas 2 : Agence Multi-Sites**
```
Organization: "Durand Immobilier"
  ├── Company: "Durand Paris" (siège)
  │    ├── Manager: Jean
  │    ├── 20 propriétés
  │    └── 45 locataires
  │
  └── Company: "Durand Lyon"
       ├── Manager: Marie
       ├── 15 propriétés
       └── 30 locataires

Admin voit: 35 propriétés total
Jean voit: 20 propriétés (Paris seulement)
Marie voit: 15 propriétés (Lyon seulement)
```

### **Cas 3 : Holding avec Filiales**
```
Organization: "Groupe ABC Holdings"
  ├── Subscription: Plan Enterprise
  │
  ├── Company: "ABC Résidentiel"
  │    ├── Managers: 3 gestionnaires
  │    ├── Properties: 100
  │    └── Tenants: 250
  │
  ├── Company: "ABC Commercial"
  │    ├── Managers: 2 gestionnaires
  │    ├── Properties: 30
  │    └── Tenants: 50
  │
  └── Company: "ABC Gestion"
       ├── Managers: 1 gestionnaire
       ├── Properties: 50
       └── Tenants: 120
```

---

## 🎨 Interface Utilisateur

### **Dashboard Admin**
```
┌──────────────────────────────────────────────┐
│  Groupe Immobilier Durand                    │
│  Plan: Professional                          │
├──────────────────────────────────────────────┤
│  📊 Vue Globale                              │
│  ├─ 3 sociétés                               │
│  ├─ 65 propriétés                            │
│  ├─ 145 locataires                           │
│  └─ 5 gestionnaires                          │
├──────────────────────────────────────────────┤
│  🏢 Mes Sociétés                             │
│  ├─ Agence Paris (20 biens)                  │
│  ├─ Agence Lyon (15 biens)                   │
│  └─ Agence Marseille (30 biens)              │
└──────────────────────────────────────────────┘
```

### **Dashboard Gestionnaire**
```
┌──────────────────────────────────────────────┐
│  Jean Dupont - Gestionnaire                  │
│  Société: Agence Paris                       │
├──────────────────────────────────────────────┤
│  📊 Ma Société                               │
│  ├─ 20 propriétés                            │
│  ├─ 45 locataires                            │
│  └─ 18 baux actifs                           │
├──────────────────────────────────────────────┤
│  ⚠️ Vous gérez uniquement: Agence Paris      │
└──────────────────────────────────────────────┘
```

---

## 🔧 Modifications Nécessaires

### **1. Entités à Modifier**

**`src/Entity/User.php`** - Ajouter relation Company :
```php
#[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'managers')]
private ?Company $company = null;

public function getCompany(): ?Company {
    return $this->company;
}
```

**`src/Entity/Property.php`** - Ajouter relation Company :
```php
#[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'properties')]
#[ORM\JoinColumn(nullable: false)]
private ?Company $company = null;
```

**`src/Entity/Tenant.php`** - Ajouter relation Company :
```php
#[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'tenants')]
#[ORM\JoinColumn(nullable: false)]
private ?Company $company = null;
```

**`src/Entity/Lease.php`** - Ajouter relation Company :
```php
#[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'leases')]
#[ORM\JoinColumn(nullable: false)]
private ?Company $company = null;
```

**`src/Entity/Organization.php`** - Ajouter relation Companies :
```php
#[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'organization')]
private Collection $companies;

public function getCompanies(): Collection {
    return $this->companies;
}
```

---

### **2. Migration SQL à créer**

```sql
-- Créer table company
CREATE TABLE company (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255),
    registration_number VARCHAR(100),
    tax_number VARCHAR(255),
    address VARCHAR(255),
    city VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    logo VARCHAR(255),
    status VARCHAR(50) NOT NULL,
    is_headquarter BOOLEAN DEFAULT 0,
    description TEXT,
    created_at DATETIME NOT NULL,
    updated_at DATETIME,
    FOREIGN KEY (organization_id) REFERENCES organization(id) ON DELETE CASCADE
);

-- Ajouter company_id aux tables existantes
ALTER TABLE user ADD company_id INT NULL;
ALTER TABLE user ADD CONSTRAINT FK_user_company 
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE SET NULL;

ALTER TABLE property ADD company_id INT NULL;
ALTER TABLE property ADD CONSTRAINT FK_property_company 
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE SET NULL;

ALTER TABLE tenant ADD company_id INT NULL;
ALTER TABLE tenant ADD CONSTRAINT FK_tenant_company 
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE SET NULL;

ALTER TABLE lease ADD company_id INT NULL;
ALTER TABLE lease ADD CONSTRAINT FK_lease_company 
    FOREIGN KEY (company_id) REFERENCES company(id) ON DELETE SET NULL;
```

---

### **3. Controllers à créer**

- `CompanyController` : CRUD des sociétés
- Modifier `PropertyController` : Filtrer par company
- Modifier `TenantController` : Filtrer par company
- Modifier `DashboardController` : Statistiques par company

---

## ✅ Avantages de cette Structure

1. ✅ **Multi-Sites** : Une organization peut gérer plusieurs agences/filiales
2. ✅ **Délégation** : Chaque société a ses propres gestionnaires
3. ✅ **Isolation** : Les gestionnaires ne voient que leur société
4. ✅ **Scalabilité** : Peut gérer des holdings complexes
5. ✅ **Reporting** : L'admin voit les stats globales ET par société
6. ✅ **Professionnel** : Structure adaptée aux grandes entreprises

---

## 🎉 Résultat Final

**AVANT** :
```
Organization
  └── Toutes les données mélangées
```

**APRÈS** :
```
Organization
  └── Company 1
       ├── Manager 1
       └── Données société 1
  └── Company 2
       ├── Manager 2
       └── Données société 2
```

**C'est une architecture multi-tenant professionnelle ! 🏢**

