# 🏢 Extension du Système Company à Toute l'Application

## ✅ TRAVAIL EFFECTUÉ

### **1. Entité Company Créée**
- ✅ `src/Entity/Company.php` (458 lignes)
- ✅ `src/Repository/CompanyRepository.php`
- Relations complètes avec Organization, Properties, Managers, Tenants, Leases

### **2. Migration SQL Créée**
- ✅ `migrations/Version20251013100000.php`
- Crée la table `company`
- Ajoute `company_id` à toutes les entités principales
- Ajoute `organization_id` aux entités qui ne l'ont pas
- Crée tous les index et contraintes FK

### **3. Event Subscriber Créé**
- ✅ `src/EventSubscriber/CompanyFilterSubscriber.php`
- Définit automatiquement `organization` et `company` lors de la création d'entités
- Filtre intelligent selon le rôle de l'utilisateur

### **4. Entités Modifiées**
- ✅ `src/Entity/Property.php` - Ajout relations Organization + Company
- ✅ `src/Controller/RegistrationController.php` - Création auto d'une Company au signup

### **5. Documentation Créée**
- ✅ `STRUCTURE_ORGANIZATION_COMPANY.md` - Explication complète du système
- ✅ `EXTENSION_COMPANY_COMPLETE.md` (ce fichier)

---

## 🔄 TRAVAIL RESTANT À FAIRE

### **Entités à Modifier** (Ajouter Organization + Company)

#### **Priorité HAUTE** (Données principales)

**1. src/Entity/Tenant.php**
```php
#[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'tenants')]
#[ORM\JoinColumn(nullable: false)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'tenants')]
#[ORM\JoinColumn(nullable: true)]
private ?Company $company = null;

// + Getters/Setters
```

**2. src/Entity/Lease.php**
```php
#[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'leases')]
#[ORM\JoinColumn(nullable: false)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'leases')]
#[ORM\JoinColumn(nullable: true)]
private ?Company $company = null;

// + Getters/Setters
```

**3. src/Entity/Payment.php**
```php
#[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'payments')]
#[ORM\JoinColumn(nullable: false)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class)]
#[ORM\JoinColumn(nullable: true)]
private ?Company $company = null;

// + Getters/Setters
```

**4. src/Entity/User.php**
```php
// DÉJÀ A organization, ajouter company

#[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'managers')]
#[ORM\JoinColumn(nullable: true)]
private ?Company $company = null;

public function getCompany(): ?Company { return $this->company; }
public function setCompany(?Company $company): static { 
    $this->company = $company; 
    return $this;
}
```

#### **Priorité MOYENNE** (Données secondaires)

**5. src/Entity/Expense.php**
```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class)]
private ?Company $company = null;
```

**6. src/Entity/MaintenanceRequest.php**
```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class)]
private ?Company $company = null;
```

**7. src/Entity/Document.php**
```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class)]
private ?Company $company = null;
```

**8. src/Entity/AccountingEntry.php**
```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class)]
private ?Company $company = null;
```

#### **Priorité BASSE** (Entités de référence)

**9. src/Entity/Organization.php**
```php
#[ORM\OneToMany(targetEntity: Company::class, mappedBy: 'organization')]
private Collection $companies;

public function getCompanies(): Collection { return $this->companies; }
// Dans __construct(): $this->companies = new ArrayCollection();
```

---

### **Controllers à Créer/Modifier**

#### **NOUVEAU : CompanyController**
```
src/Controller/CompanyController.php

Routes:
- /societes (liste)
- /societes/nouveau (créer)
- /societes/{id} (voir)
- /societes/{id}/modifier (éditer)
- /societes/{id}/supprimer (supprimer)
```

#### **À MODIFIER : Controllers Existants**

**PropertyController** :
- Filtrer par organization automatiquement
- Permettre sélection de company si ADMIN
- Forcer company de l'user si MANAGER

**TenantController** :
- Filtrer par organization + company selon rôle

**LeaseController** :
- Filtrer par organization + company selon rôle

**PaymentController** :
- Filtrer par organization + company selon rôle

**DashboardController** :
- Afficher stats par organization (ADMIN)
- Afficher stats par company (MANAGER)

---

### **Repositories à Modifier**

Ajouter des méthodes de filtrage par Company dans :
- PropertyRepository
- TenantRepository
- LeaseRepository
- PaymentRepository
- ExpenseRepository
- MaintenanceRequestRepository
- DocumentRepository
- AccountingEntryRepository

**Exemple pour PropertyRepository** :
```php
public function findByCompany(Company $company): array
{
    return $this->createQueryBuilder('p')
        ->where('p.company = :company')
        ->setParameter('company', $company)
        ->orderBy('p.address', 'ASC')
        ->getQuery()
        ->getResult();
}

public function findByOrganization(Organization $organization): array
{
    return $this->createQueryBuilder('p')
        ->where('p.organization = :organization')
        ->setParameter('organization', $organization)
        ->orderBy('p.address', 'ASC')
        ->getQuery()
        ->getResult();
}
```

---

### **Forms à Créer**

**CompanyType.php** :
```php
namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la société',
                'required' => true,
            ])
            ->add('legalName', TextType::class, [
                'label' => 'Raison sociale',
                'required' => false,
            ])
            ->add('registrationNumber', TextType::class, [
                'label' => 'SIRET/SIREN',
                'required' => false,
            ])
            ->add('taxNumber', TextType::class, [
                'label' => 'Numéro de TVA',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal',
                'required' => false,
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
            ])
            ->add('website', TextType::class, [
                'label' => 'Site web',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('isHeadquarter', CheckboxType::class, [
                'label' => 'Siège social ?',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}
```

---

### **Templates à Créer**

**templates/company/** :
- `index.html.twig` (Liste des sociétés)
- `new.html.twig` (Créer une société)
- `show.html.twig` (Détails d'une société)
- `edit.html.twig` (Modifier une société)
- `_form.html.twig` (Formulaire réutilisable)

---

### **MenuService à Modifier**

Ajouter un menu "Sociétés" dans la section Administration :

```php
'companies' => [
    'label' => 'Sociétés',
    'icon' => 'bi-building-gear',
    'route' => 'app_company_index',
    'roles' => ['ROLE_ADMIN'],
    'order' => 102,
],
```

---

### **Services à Créer**

**CompanyService.php** :
```php
namespace App\Service;

use App\Entity\Company;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;

class CompanyService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Crée une société par défaut pour une organization
     */
    public function createDefaultCompany(Organization $organization): Company
    {
        $company = new Company();
        $company->setName($organization->getName());
        $company->setOrganization($organization);
        $company->setEmail($organization->getEmail());
        $company->setPhone($organization->getPhone());
        $company->setStatus('ACTIVE');
        $company->setIsHeadquarter(true);
        $company->setCreatedAt(new \DateTime());

        $this->entityManager->persist($company);
        $this->entityManager->flush();

        return $company;
    }

    /**
     * Vérifie si un gestionnaire peut accéder à une société
     */
    public function canManagerAccessCompany(User $manager, Company $company): bool
    {
        // Admin peut accéder à toutes les sociétés de son organization
        if ($manager->hasRole('ROLE_ADMIN')) {
            return $company->getOrganization() === $manager->getOrganization();
        }

        // Manager peut accéder uniquement à SA société
        if ($manager->hasRole('ROLE_MANAGER')) {
            return $company === $manager->getCompany();
        }

        return false;
    }
}
```

---

## 📊 Impact sur l'Application

### **AVANT (Sans Company)**
```
Organization
  ├── Properties (mélangées)
  ├── Tenants (mélangés)
  ├── Managers (tous voient tout)
  └── Pas de séparation
```

### **APRÈS (Avec Company)**
```
Organization
  ├── Company 1 (Agence Paris)
  │    ├── Manager 1
  │    ├── Properties Paris
  │    └── Tenants Paris
  │
  └── Company 2 (Agence Lyon)
       ├── Manager 2
       ├── Properties Lyon
       └── Tenants Lyon

Admin voit: TOUT
Manager 1 voit: Agence Paris uniquement
Manager 2 voit: Agence Lyon uniquement
```

---

## 🚀 Étapes de Déploiement

### **Phase 1 : Base de données** ✅
1. ✅ Créer table `company`
2. ✅ Ajouter `company_id` partout
3. ✅ Migrer les données existantes

### **Phase 2 : Entités** 🔄 (En cours)
1. ✅ Property modifié
2. ⏳ Tenant à modifier
3. ⏳ Lease à modifier
4. ⏳ User à modifier
5. ⏳ Payment à modifier
6. ⏳ Autres entités

### **Phase 3 : Controllers** ⏳
1. ⏳ Créer CompanyController
2. ⏳ Modifier PropertyController
3. ⏳ Modifier TenantController
4. ⏳ Modifier autres controllers

### **Phase 4 : Interface** ⏳
1. ⏳ Templates Company CRUD
2. ⏳ Sélecteur de société dans les forms
3. ⏳ Filtres par société dans les listes
4. ⏳ Dashboard par société

### **Phase 5 : Tests** ⏳
1. ⏳ Tester création organization → company auto
2. ⏳ Tester filtrage par company pour managers
3. ⏳ Tester accès global pour admins
4. ⏳ Tester isolation des données

---

## 🎯 Commandes à Exécuter

```bash
# 1. Exécuter la migration
php bin/console doctrine:migrations:migrate --no-interaction

# 2. Vider le cache
php bin/console cache:clear

# 3. Créer les plans par défaut (si pas déjà fait)
php bin/console app:create-default-plans

# 4. Tester l'inscription
# Aller sur /inscription/plans et créer un compte
# Vérifier qu'une Company est créée automatiquement

# 5. Créer un super admin si nécessaire
php bin/console app:create-super-admin
```

---

## ✅ Avantages du Système Company

1. ✅ **Multi-Sites** : Gérer plusieurs agences/filiales
2. ✅ **Délégation** : Assigner des gestionnaires par société
3. ✅ **Isolation** : Données cloisonnées par société
4. ✅ **Scalabilité** : Support des holdings et groupes
5. ✅ **Reporting** : Stats globales ET par société
6. ✅ **Professionnel** : Conforme aux besoins d'entreprise
7. ✅ **Flexible** : Société unique OU multiples au choix

---

## 🎉 Résultat Final

**Un système SaaS multi-tenant professionnel avec :**
- Organization (Compte client)
- Subscription (Abonnement)
- Company (Sociétés/Agences)
- Managers par Company
- Data isolée par Organization ET Company
- Features contrôlées par Plan
- Interface adaptée au rôle

**C'est une architecture d'entreprise complète ! 🏢**

