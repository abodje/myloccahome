<?php

namespace App\DataFixtures;

use App\Entity\Owner;
use App\Entity\Property;
use App\Entity\Tenant;
use App\Entity\Lease;
use App\Entity\Payment;
use App\Entity\Expense;
use App\Entity\MaintenanceRequest;
use App\Entity\AccountingEntry;
use App\Entity\Document;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Créer des propriétaires
        $owner1 = new Owner();
        $owner1->setFirstName('Paul')
              ->setLastName('Arnau')
              ->setEmail('paul.arnau@example.com')
              ->setPhone('0123456789')
              ->setAddress('15 rue de la République')
              ->setCity('Lyon')
              ->setPostalCode('69001')
              ->setOwnerType('Particulier');
        $manager->persist($owner1);

        $owner2 = new Owner();
        $owner2->setFirstName('Marie')
              ->setLastName('Dubois')
              ->setEmail('marie.dubois@example.com')
              ->setPhone('0987654321')
              ->setAddress('22 avenue des Champs')
              ->setCity('Paris')
              ->setPostalCode('75008')
              ->setOwnerType('SCI');
        $manager->persist($owner2);

        // Créer des propriétés
        $property1 = new Property();
        $property1->setAddress('1-9 Avenue de Limburg')
                 ->setCity('Sainte Foy les Lyon')
                 ->setPostalCode('69110')
                 ->setPropertyType('Appartement')
                 ->setSurface(65.5)
                 ->setRooms(3)
                 ->setMonthlyRent('654.69')
                 ->setCharges('85.31')
                 ->setDeposit('654.69')
                 ->setDescription('Appartement T3 avec balcon, proche transports')
                 ->setStatus('Occupé')
                 ->setOwner($owner1);
        $manager->persist($property1);

        $property2 = new Property();
        $property2->setAddress('45 rue de la Paix')
                 ->setCity('Lyon')
                 ->setPostalCode('69002')
                 ->setPropertyType('Studio')
                 ->setSurface(25.0)
                 ->setRooms(1)
                 ->setMonthlyRent('450.00')
                 ->setCharges('50.00')
                 ->setDeposit('450.00')
                 ->setDescription('Studio meublé centre ville')
                 ->setStatus('Libre')
                 ->setOwner($owner2);
        $manager->persist($property2);

        $property3 = new Property();
        $property3->setAddress('12 Boulevard des Belges')
                 ->setCity('Lyon')
                 ->setPostalCode('69006')
                 ->setPropertyType('Appartement')
                 ->setSurface(85.5)
                 ->setRooms(4)
                 ->setMonthlyRent('1200.00')
                 ->setCharges('120.00')
                 ->setDeposit('1200.00')
                 ->setDescription('Appartement T4 avec terrasse')
                 ->setStatus('Occupé')
                 ->setOwner($owner1);
        $manager->persist($property3);

        $property4 = new Property();
        $property4->setAddress('8 Place Bellecour')
                 ->setCity('Lyon')
                 ->setPostalCode('69002')
                 ->setPropertyType('Bureau')
                 ->setSurface(120.0)
                 ->setRooms(5)
                 ->setMonthlyRent('2500.00')
                 ->setCharges('300.00')
                 ->setDeposit('5000.00')
                 ->setDescription('Bureau professionnel centre ville')
                 ->setStatus('En travaux')
                 ->setOwner($owner2);
        $manager->persist($property4);

        // Créer des locataires
        $tenant1 = new Tenant();
        $tenant1->setFirstName('Kouame')
               ->setLastName('Abodje')
               ->setEmail('kouame.abodje@example.com')
               ->setPhone('0654321987')
               ->setBirthDate(new \DateTime('1985-03-15'))
               ->setAddress('1-9 Avenue de Limburg')
               ->setCity('Sainte Foy les Lyon')
               ->setPostalCode('69110')
               ->setProfession('Ingénieur')
               ->setMonthlyIncome('3500.00');
        $manager->persist($tenant1);

        $tenant2 = new Tenant();
        $tenant2->setFirstName('Sophie')
               ->setLastName('Martin')
               ->setEmail('sophie.martin@example.com')
               ->setPhone('0765432198')
               ->setBirthDate(new \DateTime('1990-07-22'))
               ->setProfession('Professeure')
               ->setMonthlyIncome('2800.00');
        $manager->persist($tenant2);

        // Créer des contrats de location
        $lease1 = new Lease();
        $lease1->setProperty($property1)
              ->setTenant($tenant1)
              ->setStartDate(new \DateTime('2024-01-01'))
              ->setEndDate(new \DateTime('2025-12-31'))
              ->setMonthlyRent('654.69')
              ->setCharges('85.31')
              ->setDeposit('654.69')
              ->setStatus('Actif')
              ->setRentDueDay(2);
        $manager->persist($lease1);

        // Créer des paiements (historique comme dans MYFONCIA)
        $payments = [
            [
                'created' => '2025-09-25',
                'due' => '2025-10-07',
                'amount' => '654.69',
                'status' => 'Payé',
                'type' => 'Prélèvement loyer Foncia',
                'method' => 'Prélèvement automatique'
            ],
            [
                'created' => '2025-08-24',
                'due' => '2025-09-04',
                'amount' => '648.19',
                'status' => 'Payé',
                'type' => 'Prélèvement loyer Foncia',
                'method' => 'Prélèvement automatique'
            ],
            [
                'created' => '2025-08-04',
                'due' => '2025-08-04',
                'amount' => '150.00',
                'status' => 'Payé',
                'type' => 'VIR ABODJE KOUAME PAUL ARNAU',
                'method' => 'Virement'
            ],
            [
                'created' => '2025-07-31',
                'due' => '2025-07-31',
                'amount' => '150.00',
                'status' => 'Payé',
                'type' => 'VIR ABODJE KOUAME PAUL ARNAU',
                'method' => 'Virement'
            ],
            [
                'created' => '2025-07-29',
                'due' => '2025-07-29',
                'amount' => '200.00',
                'status' => 'En retard',
                'type' => 'Foncia initiation de paiement',
                'method' => 'E-paiement'
            ],
        ];

        foreach ($payments as $paymentData) {
            $payment = new Payment();
            $payment->setLease($lease1)
                   ->setCreatedAt(new \DateTime($paymentData['created']))
                   ->setDueDate(new \DateTime($paymentData['due']))
                   ->setAmount($paymentData['amount'])
                   ->setType($paymentData['type'])
                   ->setStatus($paymentData['status'])
                   ->setPaymentMethod($paymentData['method']);

            if ($paymentData['status'] === 'Payé') {
                $payment->setPaidDate(new \DateTime($paymentData['due']))
                       ->setReference('REF' . rand(100000, 999999));
            }

            $manager->persist($payment);
        }

        // Créer des dépenses
        $expense1 = new Expense();
        $expense1->setProperty($property1)
                ->setDescription('Réparation plomberie')
                ->setAmount('150.00')
                ->setCategory('Réparations')
                ->setExpenseDate(new \DateTime('2025-08-04'))
                ->setSupplier('Plomberie Dupont')
                ->setInvoiceNumber('FAC-2025-001');
        $manager->persist($expense1);

        // Créer des demandes de maintenance
        $maintenance1 = new MaintenanceRequest();
        $maintenance1->setProperty($property1)
                    ->setTenant($tenant1)
                    ->setTitle('Fuite d\'eau dans la salle de bain')
                    ->setDescription('Il y a une fuite sous l\'évier de la salle de bain')
                    ->setCategory('Plomberie')
                    ->setPriority('Haute')
                    ->setStatus('Terminée')
                    ->setRequestedDate(new \DateTime('2025-08-01'))
                    ->setCompletedDate(new \DateTime('2025-08-04'))
                    ->setAssignedTo('Plomberie Dupont')
                    ->setActualCost('150.00')
                    ->setWorkPerformed('Remplacement du joint défectueux');
        $manager->persist($maintenance1);

        $maintenance2 = new MaintenanceRequest();
        $maintenance2->setProperty($property1)
                    ->setTenant($tenant1)
                    ->setTitle('Problème de chauffage')
                    ->setDescription('Le radiateur de la chambre ne chauffe plus')
                    ->setCategory('Chauffage')
                    ->setPriority('Normale')
                    ->setStatus('En cours')
                    ->setRequestedDate(new \DateTime('2025-10-15'))
                    ->setScheduledDate(new \DateTime('2025-10-20'))
                    ->setAssignedTo('Chauffage Pro')
                    ->setEstimatedCost('200.00');
        $manager->persist($maintenance2);

        // Créer des écritures comptables
        $accountingEntries = [
            [
                'date' => '2025-10-02',
                'description' => 'PRÉLÈVEMENT DU 02/10/2025',
                'amount' => '654.69',
                'type' => 'CREDIT',
                'category' => 'LOYER',
                'reference' => 'T000ZP4FL'
            ],
            [
                'date' => '2025-10-01',
                'description' => 'APPEL POUR LA PÉRIODE DU 01 OCTOBRE AU 31 OCTOBRE',
                'amount' => '654.69',
                'type' => 'DEBIT',
                'category' => 'FRAIS_GESTION',
                'reference' => 'T000ZP4FP'
            ],
            [
                'date' => '2025-09-02',
                'description' => 'PRÉLÈVEMENT DU 02/09/2025',
                'amount' => '648.19',
                'type' => 'CREDIT',
                'category' => 'LOYER',
                'reference' => 'T000ZP4FJ'
            ],
            [
                'date' => '2025-09-01',
                'description' => 'APPEL POUR LA PÉRIODE DU 01 SEPTEMBRE AU 30 SEPTEMBRE',
                'amount' => '654.69',
                'type' => 'DEBIT',
                'category' => 'FRAIS_GESTION',
                'reference' => 'T000ZCUYQ'
            ],
            [
                'date' => '2025-08-04',
                'description' => 'VIREMENT N° 9RQQ011 DU 01/08/2025 VIR ABODJE KOUAME PAUL ARNAU',
                'amount' => '150.00',
                'type' => 'CREDIT',
                'category' => 'VIREMENT',
                'reference' => '9RQQ011'
            ],
            [
                'date' => '2025-08-02',
                'description' => 'PRÉLÈVEMENT DU 02/08/2025',
                'amount' => '685.81',
                'type' => 'CREDIT',
                'category' => 'LOYER',
                'reference' => 'T000ZP4FK'
            ],
            [
                'date' => '2025-08-01',
                'description' => 'APPEL POUR LA PÉRIODE DU 01 AOÛT AU 31 AOÛT',
                'amount' => '685.81',
                'type' => 'DEBIT',
                'category' => 'FRAIS_GESTION',
                'reference' => 'T000ZP4FL'
            ],
            [
                'date' => '2025-07-31',
                'description' => 'VIREMENT N° 9PP6SV6 DU 30/07/2025 VIR ABODJE KOUAME PAUL ARNAU',
                'amount' => '150.00',
                'type' => 'CREDIT',
                'category' => 'VIREMENT',
                'reference' => '9PP6SV6'
            ]
        ];

        $runningBalance = 0;
        foreach ($accountingEntries as $entryData) {
            $entry = new AccountingEntry();
            $entry->setEntryDate(new \DateTime($entryData['date']))
                 ->setDescription($entryData['description'])
                 ->setAmount($entryData['amount'])
                 ->setType($entryData['type'])
                 ->setCategory($entryData['category'])
                 ->setReference($entryData['reference'])
                 ->setProperty($property1)
                 ->setOwner($owner1);

            // Calculer le solde courant
            if ($entryData['type'] === 'CREDIT') {
                $runningBalance += (float)$entryData['amount'];
            } else {
                $runningBalance -= (float)$entryData['amount'];
            }
            $entry->setRunningBalance((string)$runningBalance);

            $manager->persist($entry);
        }

        // Créer des documents de test
        $documents = [
            [
                'name' => 'Contrat d\'assurance habitation 2025',
                'type' => 'Assurance',
                'description' => 'Police d\'assurance multirisque habitation',
                'document_date' => '2025-01-01',
                'expiration_date' => '2025-12-31',
                'property' => $property1
            ],
            [
                'name' => 'Quittance de loyer Octobre 2025',
                'type' => 'Avis d\'échéance',
                'description' => 'Quittance de loyer pour le mois d\'octobre 2025',
                'document_date' => '2025-10-01',
                'property' => $property1,
                'tenant' => $tenant1
            ],
            [
                'name' => 'Contrat de location Kouame Abodje',
                'type' => 'Contrat de location',
                'description' => 'Bail de location signé avec M. Kouame Abodje',
                'document_date' => '2024-01-01',
                'property' => $property1,
                'tenant' => $tenant1,
                'lease' => $lease1
            ],
            [
                'name' => 'Diagnostic de Performance Énergétique',
                'type' => 'Diagnostics',
                'description' => 'DPE réalisé par un diagnostiqueur certifié',
                'document_date' => '2024-06-15',
                'expiration_date' => '2034-06-15',
                'property' => $property1
            ],
            [
                'name' => 'Guide du locataire MYLOCCA',
                'type' => 'Conseils',
                'description' => 'Conseils et informations pour bien gérer votre location',
                'document_date' => '2025-01-01'
            ],
            [
                'name' => 'État des lieux d\'entrée',
                'type' => 'État des lieux',
                'description' => 'État des lieux réalisé à l\'entrée du locataire',
                'document_date' => '2024-01-01',
                'property' => $property1,
                'tenant' => $tenant1,
                'lease' => $lease1
            ]
        ];

        foreach ($documents as $docData) {
            $document = new Document();
            $document->setName($docData['name'])
                    ->setType($docData['type'])
                    ->setDescription($docData['description'])
                    ->setDocumentDate(new \DateTime($docData['document_date']))
                    ->setFileName('sample_' . uniqid() . '.pdf')
                    ->setOriginalFileName($docData['name'] . '.pdf')
                    ->setMimeType('application/pdf')
                    ->setFileSize(rand(50000, 500000));

            if (isset($docData['expiration_date'])) {
                $document->setExpirationDate(new \DateTime($docData['expiration_date']));
            }

            if (isset($docData['property'])) {
                $document->setProperty($docData['property']);
            }

            if (isset($docData['tenant'])) {
                $document->setTenant($docData['tenant']);
            }

            if (isset($docData['lease'])) {
                $document->setLease($docData['lease']);
            }

            $manager->persist($document);
        }

        $manager->flush();
    }
}
