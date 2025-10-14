# 🔄 Impact du Système Company sur TOUT le Système

## ✅ OUI, Company DOIT être répercuté sur :

### **1. 📄 Reçus de Loyer (PDF)**
### **2. 📋 Tâches Console**
### **3. 📁 Documents**
### **4. 💰 Paiements**
### **5. 📊 Comptabilité**
### **6. 📧 Emails & SMS**

---

## 📄 1. REÇUS DE LOYER (PDF)

### **Fichiers Concernés**

**`src/Service/RentReceiptService.php`** :
```php
// AVANT (Sans Company)
public function generateRentReceipt(Payment $payment): Document
{
    $data = [
        'payment' => $payment,
        'tenant' => $payment->getLease()->getTenant(),
        'property' => $payment->getLease()->getProperty(),
        'organization' => $payment->getLease()->getProperty()->getOwner() // ❌ Pas clair
    ];
}

// APRÈS (Avec Company)
public function generateRentReceipt(Payment $payment): Document
{
    $company = $payment->getCompany(); // ✅ Société émettrice
    $organization = $payment->getOrganization(); // ✅ Organization parente
    
    $data = [
        'payment' => $payment,
        'tenant' => $payment->getLease()->getTenant(),
        'property' => $payment->getLease()->getProperty(),
        'company' => $company, // ✅ Infos de la société émettrice
        'organization' => $organization,
        // Coordonnées de la société sur le reçu
        'company_name' => $company->getLegalName() ?: $company->getName(),
        'company_address' => $company->getAddress(),
        'company_city' => $company->getCity(),
        'company_postal_code' => $company->getPostalCode(),
        'company_siret' => $company->getRegistrationNumber(),
        'company_email' => $company->getEmail(),
        'company_phone' => $company->getPhone(),
    ];
}
```

### **Template PDF à Modifier**

**`templates/pdf/rent_receipt.html.twig`** :
```twig
{# AVANT #}
<div class="header">
    <h1>{{ organization.name }}</h1>
    <p>{{ organization.email }}</p>
</div>

{# APRÈS #}
<div class="header">
    <div class="company-info">
        <h1>{{ company.legalName ?: company.name }}</h1>
        {% if company.registrationNumber %}
            <p><strong>SIRET :</strong> {{ company.registrationNumber }}</p>
        {% endif %}
        <p>{{ company.address }}</p>
        <p>{{ company.postalCode }} {{ company.city }}</p>
        <p><strong>Email :</strong> {{ company.email }}</p>
        <p><strong>Tél :</strong> {{ company.phone }}</p>
    </div>
</div>

{# Dans le pied de page #}
<div class="footer">
    <p>{{ company.legalName ?: company.name }} - SIRET: {{ company.registrationNumber }}</p>
    <p>{{ company.address }}, {{ company.postalCode }} {{ company.city }}</p>
</div>
```

---

## 📋 2. TÂCHES CONSOLE

### **A. Génération des Loyers (GenerateRentCommand)**

**`src/Command/GenerateRentCommand.php`** :
```php
// AVANT (Sans Company)
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $leases = $this->leaseRepository->findActiveLeases();
    
    foreach ($leases as $lease) {
        // Générer paiement
        $payment = new Payment();
        $payment->setLease($lease);
        // ❌ Pas de company
    }
}

// APRÈS (Avec Company)
protected function execute(InputInterface $input, OutputInterface $output): int
{
    // Option 1: Générer pour toutes les companies
    $companies = $this->companyRepository->findBy(['status' => 'ACTIVE']);
    
    foreach ($companies as $company) {
        $io->section("Génération des loyers pour: {$company->getName()}");
        
        $leases = $this->leaseRepository->findActiveByCompany($company);
        
        foreach ($leases as $lease) {
            $payment = new Payment();
            $payment->setLease($lease);
            $payment->setCompany($company); // ✅ Associer la company
            $payment->setOrganization($company->getOrganization()); // ✅ Associer l'organization
            
            $this->entityManager->persist($payment);
        }
    }
    
    // Option 2: Filtrer par organization si SUPER_ADMIN
    // Option 3: Ajouter option --company=X pour une société spécifique
}
```

**Nouvelle Option CLI** :
```bash
# Générer pour toutes les sociétés
php bin/console app:generate-rent

# Générer pour une société spécifique
php bin/console app:generate-rent --company=1

# Générer pour une organization spécifique
php bin/console app:generate-rent --organization=5
```

### **B. Génération des Documents (GenerateRentDocumentsCommand)**

**`src/Command/GenerateRentDocumentsCommand.php`** :
```php
// APRÈS (Avec Company)
protected function execute(InputInterface $input, OutputInterface $output): int
{
    $companyId = $input->getOption('company');
    
    if ($companyId) {
        $companies = [$this->companyRepository->find($companyId)];
    } else {
        $companies = $this->companyRepository->findBy(['status' => 'ACTIVE']);
    }
    
    foreach ($companies as $company) {
        $io->section("Génération des documents pour: {$company->getName()}");
        
        // Générer les quittances pour cette company
        $payments = $this->paymentRepository->findPaidByCompany($company, $month);
        
        foreach ($payments as $payment) {
            $this->rentReceiptService->generateRentReceipt($payment);
        }
        
        // Générer les avis d'échéance pour cette company
        $upcomingPayments = $this->paymentRepository->findPendingByCompany($company, $nextMonth);
        
        foreach ($upcomingPayments as $payment) {
            $this->rentReceiptService->generatePaymentNotice($payment);
        }
    }
}
```

### **C. Rappels de Paiement (TaskManagerService)**

**`src/Service/TaskManagerService.php`** :
```php
private function executePaymentReminderTask(Task $task): void
{
    // Filtrer les paiements en retard PAR COMPANY
    $companies = $this->entityManager->getRepository(Company::class)
        ->findBy(['status' => 'ACTIVE']);
    
    foreach ($companies as $company) {
        $overduePayments = $this->entityManager
            ->getRepository(Payment::class)
            ->findOverdueByCompany($company);
        
        foreach ($overduePayments as $payment) {
            // Email avec les coordonnées de la COMPANY émettrice
            $this->sendPaymentReminderEmail($payment, $company);
            
            // SMS avec les coordonnées de la COMPANY
            if ($this->settingsService->get('orange_sms_enabled')) {
                $this->sendPaymentReminderSms($payment, $company);
            }
        }
    }
}

private function sendPaymentReminderEmail(Payment $payment, Company $company): void
{
    $tenant = $payment->getLease()->getTenant();
    
    $email = (new Email())
        ->from(new Address($company->getEmail(), $company->getName())) // ✅ Email de la company
        ->to($tenant->getEmail())
        ->subject("Rappel de paiement - {$company->getName()}")
        ->html($this->twig->render('emails/payment_reminder.html.twig', [
            'payment' => $payment,
            'tenant' => $tenant,
            'company' => $company, // ✅ Infos de la company dans l'email
        ]));
    
    $this->mailer->send($email);
}

private function sendPaymentReminderSms(Payment $payment, Company $company): void
{
    $tenant = $payment->getLease()->getTenant();
    
    $message = sprintf(
        "Rappel %s: Loyer de %s FCFA du. Payez sur %s. Info: %s",
        $company->getName(), // ✅ Nom de la company
        $payment->getAmount(),
        $company->getWebsite() ?: 'notre site',
        $company->getPhone() // ✅ Téléphone de la company
    );
    
    $this->orangeSmsService->sendSms($tenant->getPhone(), $message);
}
```

---

## 📁 3. DOCUMENTS

### **Entité Document à Modifier**

**`src/Entity/Document.php`** :
```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
#[ORM\JoinColumn(nullable: false)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class)]
#[ORM\JoinColumn(nullable: true)]
private ?Company $company = null;

public function getCompany(): ?Company { return $this->company; }
public function setCompany(?Company $company): static { 
    $this->company = $company; 
    return $this;
}
```

### **Service de Génération de Documents**

**`src/Service/RentReceiptService.php`** - Modification complète :
```php
public function generateRentReceipt(Payment $payment): Document
{
    $lease = $payment->getLease();
    $tenant = $lease->getTenant();
    $property = $lease->getProperty();
    $company = $payment->getCompany() ?: $property->getCompany(); // ✅ Company
    $organization = $payment->getOrganization(); // ✅ Organization
    
    // Rendu du PDF avec les infos de la company
    $html = $this->twig->render('pdf/rent_receipt.html.twig', [
        'payment' => $payment,
        'lease' => $lease,
        'tenant' => $tenant,
        'property' => $property,
        'company' => $company, // ✅ Données de la société
        'organization' => $organization,
        'date' => new \DateTime(),
    ]);
    
    // Générer le PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    
    // Sauvegarder le fichier
    $fileName = sprintf(
        'quittance_%s_%s_%s.pdf',
        $company->getRegistrationNumber() ?: $company->getId(), // ✅ SIRET de la company
        $tenant->getId(),
        $payment->getPaymentDate()->format('Y-m')
    );
    
    // Créer l'entité Document
    $document = new Document();
    $document->setName('Quittance de loyer');
    $document->setType('Quittance');
    $document->setFileName($fileName);
    $document->setTenant($tenant);
    $document->setProperty($property);
    $document->setLease($lease);
    $document->setOrganization($organization); // ✅
    $document->setCompany($company); // ✅ Associer la company
    $document->setDocumentDate($payment->getPaymentDate());
    
    $this->entityManager->persist($document);
    $this->entityManager->flush();
    
    return $document;
}
```

### **Filtrage des Documents par Company**

**`src/Repository/DocumentRepository.php`** :
```php
public function findByCompany(Company $company, ?string $type = null): array
{
    $qb = $this->createQueryBuilder('d')
        ->where('d.company = :company')
        ->setParameter('company', $company)
        ->orderBy('d.documentDate', 'DESC');
    
    if ($type) {
        $qb->andWhere('d.type = :type')
           ->setParameter('type', $type);
    }
    
    return $qb->getQuery()->getResult();
}

public function findByOrganization(Organization $organization, ?string $type = null): array
{
    $qb = $this->createQueryBuilder('d')
        ->where('d.organization = :organization')
        ->setParameter('organization', $organization)
        ->orderBy('d.documentDate', 'DESC');
    
    if ($type) {
        $qb->andWhere('d.type = :type')
           ->setParameter('type', $type);
    }
    
    return $qb->getQuery()->getResult();
}
```

---

## 💰 4. PAIEMENTS

### **Service AdvancePaymentService**

**`src/Service/AdvancePaymentService.php`** :
```php
public function createAdvancePayment(
    Tenant $tenant,
    ?Lease $lease,
    string $amount,
    Company $company // ✅ Ajouter le paramètre
): AdvancePayment {
    $advancePayment = new AdvancePayment();
    $advancePayment->setTenant($tenant);
    $advancePayment->setLease($lease);
    $advancePayment->setAmount($amount);
    $advancePayment->setAvailableBalance($amount);
    $advancePayment->setOrganization($tenant->getOrganization()); // ✅
    $advancePayment->setCompany($company); // ✅ Company
    $advancePayment->setStatus('ACTIVE');
    $advancePayment->setCreatedAt(new \DateTime());
    
    $this->entityManager->persist($advancePayment);
    $this->entityManager->flush();
    
    return $advancePayment;
}
```

---

## 📊 5. COMPTABILITÉ

### **Entité AccountingEntry**

**`src/Entity/AccountingEntry.php`** :
```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
private ?Organization $organization = null;

#[ORM\ManyToOne(targetEntity: Company::class)]
private ?Company $company = null;

// Dans le service AccountingService
public function recordPayment(Payment $payment): void
{
    $company = $payment->getCompany();
    
    // Débit (Encaissement)
    $debit = new AccountingEntry();
    $debit->setType('DEBIT');
    $debit->setAmount($payment->getAmount());
    $debit->setDescription("Loyer - " . $payment->getLease()->getProperty()->getFullAddress());
    $debit->setPayment($payment);
    $debit->setOrganization($payment->getOrganization()); // ✅
    $debit->setCompany($company); // ✅ Company
    $debit->setEntryDate($payment->getPaymentDate());
    
    $this->entityManager->persist($debit);
}
```

### **Reporting par Company**

```php
public function getCompanyRevenue(Company $company, \DateTime $startDate, \DateTime $endDate): float
{
    return $this->accountingEntryRepository->createQueryBuilder('ae')
        ->select('SUM(ae.amount)')
        ->where('ae.company = :company')
        ->andWhere('ae.type = :type')
        ->andWhere('ae.entryDate BETWEEN :start AND :end')
        ->setParameter('company', $company)
        ->setParameter('type', 'DEBIT')
        ->setParameter('start', $startDate)
        ->setParameter('end', $endDate)
        ->getQuery()
        ->getSingleScalarResult() ?? 0;
}
```

---

## 📧 6. EMAILS & SMS

### **Templates Email à Modifier**

**`templates/emails/payment_reminder.html.twig`** :
```twig
<!DOCTYPE html>
<html>
<head>
    <title>Rappel de paiement</title>
</head>
<body>
    <div style="max-width: 600px; margin: 0 auto;">
        {# En-tête avec logo de la company #}
        <div style="background: #f8f9fa; padding: 20px; text-align: center;">
            {% if company.logo %}
                <img src="{{ company.logo }}" alt="{{ company.name }}" style="max-width: 200px;">
            {% endif %}
            <h2>{{ company.legalName ?: company.name }}</h2>
        </div>
        
        {# Corps du message #}
        <div style="padding: 20px;">
            <p>Bonjour {{ tenant.firstName }} {{ tenant.lastName }},</p>
            
            <p>Nous vous rappelons que votre loyer du mois de {{ payment.dueDate|date('F Y') }} 
               d'un montant de <strong>{{ payment.amount|currency }}</strong> est en attente de paiement.</p>
            
            <p><strong>Détails du paiement :</strong></p>
            <ul>
                <li>Montant : {{ payment.amount|currency }}</li>
                <li>Date d'échéance : {{ payment.dueDate|date('d/m/Y') }}</li>
                <li>Propriété : {{ payment.lease.property.fullAddress }}</li>
            </ul>
        </div>
        
        {# Pied de page avec coordonnées de la company #}
        <div style="background: #f8f9fa; padding: 20px; margin-top: 30px; font-size: 12px;">
            <p><strong>{{ company.legalName ?: company.name }}</strong></p>
            {% if company.registrationNumber %}
                <p>SIRET : {{ company.registrationNumber }}</p>
            {% endif %}
            <p>{{ company.address }}, {{ company.postalCode }} {{ company.city }}</p>
            <p>
                Email : {{ company.email }} | 
                Tél : {{ company.phone }}
                {% if company.website %} | 
                    Web : <a href="{{ company.website }}">{{ company.website }}</a>
                {% endif %}
            </p>
        </div>
    </div>
</body>
</html>
```

---

## 🔧 MODIFICATIONS NÉCESSAIRES - CHECKLIST

### **Fichiers à Modifier** :

#### **Services** :
- [x] `RentReceiptService.php` - Ajouter company aux PDFs
- [ ] `AccountingService.php` - Enregistrer company dans les écritures
- [ ] `AdvancePaymentService.php` - Associer company aux acomptes
- [ ] `TaskManagerService.php` - Filtrer par company
- [ ] `NotificationService.php` - Utiliser infos company dans emails/SMS

#### **Commands** :
- [ ] `GenerateRentCommand.php` - Option --company
- [ ] `GenerateRentDocumentsCommand.php` - Filtrer par company
- [ ] Tous les autres commands qui génèrent des données

#### **Entities** :
- [x] `Property.php` - ✅ Fait
- [ ] `Tenant.php` - Ajouter organization + company
- [ ] `Lease.php` - Ajouter organization + company
- [ ] `Payment.php` - Ajouter organization + company
- [ ] `Document.php` - Ajouter organization + company
- [ ] `AccountingEntry.php` - Ajouter organization + company
- [ ] `AdvancePayment.php` - Ajouter organization + company
- [ ] `Expense.php` - Ajouter organization + company
- [ ] `MaintenanceRequest.php` - Ajouter organization + company

#### **Templates** :
- [ ] `pdf/rent_receipt.html.twig` - Afficher infos company
- [ ] `pdf/payment_notice.html.twig` - Afficher infos company
- [ ] `pdf/lease_contract.html.twig` - Afficher infos company
- [ ] `emails/*.html.twig` - Utiliser infos company

#### **Repositories** :
- [ ] Tous les repositories - Ajouter méthodes `findByCompany()` et `findByOrganization()`

---

## 🎯 PRIORITÉS D'IMPLÉMENTATION

### **Phase 1 : CRITIQUE** (Faire maintenant)
1. ✅ Migration base de données
2. ⏳ Modifier toutes les entités
3. ⏳ Modifier RentReceiptService (PDF avec company)
4. ⏳ Modifier les commands pour filtrer par company

### **Phase 2 : IMPORTANT** (Faire ensuite)
1. Modifier AccountingService
2. Modifier templates emails
3. Modifier TaskManagerService

### **Phase 3 : AMÉLIORATION** (Faire après)
1. Ajouter options CLI --company
2. Dashboard par company
3. Reporting par company

---

## ✅ RÉSUMÉ

**OUI, Company DOIT être répercuté sur :**

1. ✅ **Reçus PDF** - Coordonnées de la company émettrice
2. ✅ **Tâches Console** - Filtrage et génération par company
3. ✅ **Documents** - Association company + filtrage
4. ✅ **Paiements** - Traçabilité par company
5. ✅ **Comptabilité** - Écritures par company
6. ✅ **Emails/SMS** - Infos de la company émettrice

**Sans cela, le système Company ne serait pas complet !**

C'est un travail conséquent mais NÉCESSAIRE pour un système multi-sociétés professionnel ! 🏢


