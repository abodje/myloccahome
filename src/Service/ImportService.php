<?php

namespace App\Service;

use App\Entity\Property;
use App\Entity\Tenant;
use App\Entity\Lease;
use App\Entity\Payment;
use App\Entity\User;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

class ImportService
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private LoggerInterface $logger;
    private array $errors = [];
    private array $warnings = [];
    private array $stats = [
        'properties' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'tenants' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'leases' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        'payments' => ['created' => 0, 'updated' => 0, 'errors' => 0],
    ];

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->logger = $logger;
    }

    /**
     * Parse un fichier CSV et retourne les données sous forme de tableau
     */
    public function parseCSV(string $filePath, string $delimiter = ','): array
    {
        $rows = [];

        if (!file_exists($filePath)) {
            throw new \Exception("Le fichier CSV n'existe pas: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \Exception("Impossible d'ouvrir le fichier CSV");
        }

        // Lire les en-têtes
        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false) {
            fclose($handle);
            throw new \Exception("Le fichier CSV est vide ou mal formaté");
        }

        // Nettoyer les en-têtes (supprimer BOM UTF-8 si présent)
        $headers = array_map(function($header) {
            return trim(str_replace("\xEF\xBB\xBF", '', $header));
        }, $headers);

        $lineNumber = 1;
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;

            // Ignorer les lignes vides
            if (empty(array_filter($data))) {
                continue;
            }

            // Créer un tableau associatif avec les en-têtes
            if (count($data) !== count($headers)) {
                $this->addWarning("Ligne {$lineNumber}: nombre de colonnes incorrect (attendu: " . count($headers) . ", reçu: " . count($data) . ")");
                continue;
            }

            $row = array_combine($headers, $data);
            $row['_line_number'] = $lineNumber;
            $rows[] = $row;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Import complet à partir d'un fichier CSV
     */
    public function importFromCSV(string $filePath, Organization $organization, string $delimiter = ','): array
    {
        $this->resetStats();

        try {
            $rows = $this->parseCSV($filePath, $delimiter);

            if (empty($rows)) {
                throw new \Exception("Aucune donnée à importer dans le fichier CSV");
            }

            // Traiter chaque ligne
            foreach ($rows as $row) {
                $this->processRow($row, $organization);
            }

            // Sauvegarder toutes les entités
            $this->entityManager->flush();

            $this->logger->info('Import CSV terminé', [
                'stats' => $this->stats,
                'errors' => count($this->errors),
                'warnings' => count($this->warnings),
            ]);

        } catch (\Exception $e) {
            $this->addError("Erreur fatale: " . $e->getMessage());
            $this->logger->error('Erreur lors de l\'import CSV', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return [
            'stats' => $this->stats,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'success' => empty($this->errors),
        ];
    }

    /**
     * Traite une ligne du CSV
     */
    private function processRow(array $row, Organization $organization): void
    {
        $lineNumber = $row['_line_number'] ?? '?';

        try {
            // 1. Créer ou récupérer le bien
            $property = $this->importProperty($row, $organization, $lineNumber);
            if (!$property) {
                return; // Erreur déjà enregistrée
            }

            // 2. Créer ou récupérer le locataire
            $tenant = $this->importTenant($row, $organization, $lineNumber);
            if (!$tenant) {
                return; // Erreur déjà enregistrée
            }

            // 3. Créer le bail
            $lease = $this->importLease($row, $property, $tenant, $lineNumber);
            if (!$lease) {
                return; // Erreur déjà enregistrée
            }

            // 4. Créer les paiements de loyer
            $this->importPayments($row, $lease, $lineNumber);

        } catch (\Exception $e) {
            $this->addError("Ligne {$lineNumber}: " . $e->getMessage());
            $this->stats['properties']['errors']++;
        }
    }

    /**
     * Import un bien immobilier
     */
    private function importProperty(array $row, Organization $organization, int $lineNumber): ?Property
    {
        try {
            // Champs requis
            $address = $this->getRequiredField($row, 'adresse_bien', $lineNumber);

            // Vérifier si le bien existe déjà (par adresse)
            $property = $this->entityManager->getRepository(Property::class)
                ->findOneBy(['address' => $address, 'organization' => $organization]);

            if ($property) {
                $this->stats['properties']['updated']++;
                return $property;
            }

            // Créer le nouveau bien
            $property = new Property();
            $property->setAddress($address);
            $property->setCity($this->getField($row, 'ville_bien', 'Non spécifié'));
            $property->setPostalCode($this->getField($row, 'code_postal_bien', ''));
            $property->setPropertyType($this->getField($row, 'type_bien', 'Appartement'));
            $property->setRooms((int) $this->getField($row, 'nb_pieces', 1));
            $property->setSurface((float) $this->getField($row, 'surface', 0));
            $property->setMonthlyRent((float) $this->getRequiredField($row, 'loyer_mensuel', $lineNumber));
            $property->setCharges((float) $this->getField($row, 'charges', 0));
            $property->setStatus($this->getField($row, 'statut_bien', 'Loué'));
            $property->setOrganization($organization);

            $this->entityManager->persist($property);
            $this->stats['properties']['created']++;

            return $property;

        } catch (\Exception $e) {
            $this->addError("Ligne {$lineNumber} (Bien): " . $e->getMessage());
            $this->stats['properties']['errors']++;
            return null;
        }
    }

    /**
     * Import un locataire
     */
    private function importTenant(array $row, Organization $organization, int $lineNumber): ?Tenant
    {
        try {
            // Champs requis
            $email = $this->getRequiredField($row, 'email_locataire', $lineNumber);

            // Valider l'email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Email invalide: {$email}");
            }

            // Vérifier si le locataire existe déjà
            $tenant = $this->entityManager->getRepository(Tenant::class)
                ->findOneBy(['email' => $email, 'organization' => $organization]);

            if ($tenant) {
                $this->stats['tenants']['updated']++;
                return $tenant;
            }

            // Créer le nouveau locataire
            $tenant = new Tenant();
            $tenant->setFirstName($this->getRequiredField($row, 'prenom_locataire', $lineNumber));
            $tenant->setLastName($this->getRequiredField($row, 'nom_locataire', $lineNumber));
            $tenant->setEmail($email);
            $tenant->setPhone($this->getField($row, 'telephone_locataire', ''));
            $tenant->setAddress($this->getField($row, 'adresse_locataire', ''));
            $tenant->setCity($this->getField($row, 'ville_locataire', ''));
            $tenant->setPostalCode($this->getField($row, 'code_postal_locataire', ''));
            $tenant->setOrganization($organization);

            // Créer le compte utilisateur associé
            $user = $this->createUserForTenant($tenant, $organization);
            $tenant->setUser($user);

            $this->entityManager->persist($tenant);
            $this->stats['tenants']['created']++;

            return $tenant;

        } catch (\Exception $e) {
            $this->addError("Ligne {$lineNumber} (Locataire): " . $e->getMessage());
            $this->stats['tenants']['errors']++;
            return null;
        }
    }

    /**
     * Import un bail
     */
    private function importLease(array $row, Property $property, Tenant $tenant, int $lineNumber): ?Lease
    {
        try {
            // Champs requis
            $startDate = $this->parseDateField($row, 'date_debut_bail', $lineNumber);

            // Vérifier si un bail existe déjà pour ce bien et ce locataire
            $lease = $this->entityManager->getRepository(Lease::class)
                ->findOneBy([
                    'property' => $property,
                    'tenant' => $tenant,
                    'startDate' => $startDate,
                ]);

            if ($lease) {
                $this->stats['leases']['updated']++;
                return $lease;
            }

            // Créer le nouveau bail
            $lease = new Lease();
            $lease->setProperty($property);
            $lease->setTenant($tenant);
            $lease->setStartDate($startDate);

            // Date de fin (optionnelle)
            if ($this->hasField($row, 'date_fin_bail')) {
                $endDate = $this->parseDateField($row, 'date_fin_bail', $lineNumber, false);
                if ($endDate) {
                    $lease->setEndDate($endDate);
                }
            }

            $lease->setMonthlyRent((float) $this->getRequiredField($row, 'loyer_mensuel', $lineNumber));
            $lease->setCharges((float) $this->getField($row, 'charges', 0));
            $lease->setDeposit((float) $this->getField($row, 'depot_garantie', 0));
            $lease->setStatus($this->getField($row, 'statut_bail', 'Actif'));

            $this->entityManager->persist($lease);
            $this->stats['leases']['created']++;

            return $lease;

        } catch (\Exception $e) {
            $this->addError("Ligne {$lineNumber} (Bail): " . $e->getMessage());
            $this->stats['leases']['errors']++;
            return null;
        }
    }

    /**
     * Import les paiements de loyer
     */
    private function importPayments(array $row, Lease $lease, int $lineNumber): void
    {
        try {
            // Si on a une date de premier paiement, créer les échéances
            if ($this->hasField($row, 'date_premier_paiement')) {
                $firstPaymentDate = $this->parseDateField($row, 'date_premier_paiement', $lineNumber, false);

                if ($firstPaymentDate) {
                    $nbMonths = (int) $this->getField($row, 'nb_echeances', 12);
                    $this->generatePaymentSchedule($lease, $firstPaymentDate, $nbMonths);
                }
            }

        } catch (\Exception $e) {
            $this->addError("Ligne {$lineNumber} (Paiements): " . $e->getMessage());
            $this->stats['payments']['errors']++;
        }
    }

    /**
     * Génère un échéancier de paiements
     */
    private function generatePaymentSchedule(Lease $lease, \DateTime $startDate, int $nbMonths): void
    {
        $amount = $lease->getMonthlyRent() + $lease->getCharges();
        $currentDate = clone $startDate;

        for ($i = 0; $i < $nbMonths; $i++) {
            // Vérifier si un paiement existe déjà pour ce mois
            $existingPayment = $this->entityManager->getRepository(Payment::class)
                ->findOneBy([
                    'lease' => $lease,
                    'dueDate' => $currentDate,
                ]);

            if (!$existingPayment) {
                $payment = new Payment();
                $payment->setLease($lease);
                $payment->setAmount($amount);
                $payment->setDueDate(clone $currentDate);
                $payment->setStatus('En attente');
                $payment->setType('Loyer');

                $this->entityManager->persist($payment);
                $this->stats['payments']['created']++;
            }

            // Passer au mois suivant
            $currentDate->modify('+1 month');
        }
    }

    /**
     * Crée un compte utilisateur pour un locataire
     */
    private function createUserForTenant(Tenant $tenant, Organization $organization): User
    {
        // Vérifier si l'utilisateur existe déjà
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $tenant->getEmail()]);

        if ($user) {
            return $user;
        }

        // Créer un nouveau compte
        $user = new User();
        $user->setEmail($tenant->getEmail());
        $user->setFirstName($tenant->getFirstName());
        $user->setLastName($tenant->getLastName());
        $user->setPhone($tenant->getPhone());
        $user->setRoles(['ROLE_TENANT']);
        $user->setOrganization($organization);

        // Mot de passe temporaire : "locataire" + 4 derniers chiffres du téléphone
        $phone = preg_replace('/[^0-9]/', '', $tenant->getPhone());
        $tempPassword = 'locataire' . substr($phone, -4);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $tempPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);

        return $user;
    }

    /**
     * Utilitaires
     */
    private function getField(array $row, string $fieldName, $default = null)
    {
        return isset($row[$fieldName]) && $row[$fieldName] !== '' ? trim($row[$fieldName]) : $default;
    }

    private function getRequiredField(array $row, string $fieldName, int $lineNumber)
    {
        if (!isset($row[$fieldName]) || trim($row[$fieldName]) === '') {
            throw new \Exception("Le champ '{$fieldName}' est requis");
        }
        return trim($row[$fieldName]);
    }

    private function hasField(array $row, string $fieldName): bool
    {
        return isset($row[$fieldName]) && trim($row[$fieldName]) !== '';
    }

    private function parseDateField(array $row, string $fieldName, int $lineNumber, bool $required = true): ?\DateTime
    {
        $value = $this->getField($row, $fieldName);

        if ($value === null) {
            if ($required) {
                throw new \Exception("Le champ date '{$fieldName}' est requis");
            }
            return null;
        }

        // Essayer différents formats de date
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date;
            }
        }

        throw new \Exception("Format de date invalide pour '{$fieldName}': {$value} (formats acceptés: YYYY-MM-DD, DD/MM/YYYY)");
    }

    private function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    private function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    private function resetStats(): void
    {
        $this->errors = [];
        $this->warnings = [];
        $this->stats = [
            'properties' => ['created' => 0, 'updated' => 0, 'errors' => 0],
            'tenants' => ['created' => 0, 'updated' => 0, 'errors' => 0],
            'leases' => ['created' => 0, 'updated' => 0, 'errors' => 0],
            'payments' => ['created' => 0, 'updated' => 0, 'errors' => 0],
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}
