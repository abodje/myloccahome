<?php

namespace App\Service;

use App\Repository\PropertyRepository;
use App\Repository\TenantRepository;
use App\Repository\LeaseRepository;
use App\Repository\PaymentRepository;
use App\Repository\DocumentRepository;
use App\Repository\MaintenanceRequestRepository;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service de recherche globale intelligente multi-entités
 */
class GlobalSearchService
{
    public function __construct(
        private PropertyRepository $propertyRepo,
        private TenantRepository $tenantRepo,
        private LeaseRepository $leaseRepo,
        private PaymentRepository $paymentRepo,
        private DocumentRepository $documentRepo,
        private MaintenanceRequestRepository $maintenanceRepo,
        private Security $security
    ) {
    }

    /**
     * Recherche globale dans toutes les entités
     */
    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [];
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        // Log pour débogage
        if ($user) {
            error_log("GlobalSearch - User: " . $user->getEmail() .
                     ", Roles: " . implode(', ', $user->getRoles()) .
                     ", Organization: " . ($user->getOrganization() ? $user->getOrganization()->getName() : 'None') .
                     ", Company: " . ($user->getCompany() ? $user->getCompany()->getName() : 'None'));
        }

        $results = [];

        // Rechercher dans chaque entité avec filtrage multi-tenant
        $results['properties'] = $this->searchProperties($query, $user, $limit);
        $results['tenants'] = $this->searchTenants($query, $user, $limit);
        $results['leases'] = $this->searchLeases($query, $user, $limit);
        $results['payments'] = $this->searchPayments($query, $user, $limit);
        $results['documents'] = $this->searchDocuments($query, $user, $limit);
        $results['maintenance'] = $this->searchMaintenance($query, $user, $limit);

        // Log des résultats
        $totalResults = array_sum(array_map('count', $results));
        error_log("GlobalSearch - Query: '$query', Total results: $totalResults");

        return $results;
    }

    /**
     * Recherche simplifiée pour suggestions (autocomplete)
     */
    public function quickSearch(string $query, int $limit = 10): array
    {
        $results = $this->search($query, $limit);

        // Formater pour autocomplete
        $suggestions = [];

        foreach ($results as $entityType => $items) {
            foreach ($items as $item) {
                $suggestions[] = $this->formatForAutocomplete($item, $entityType);
            }
        }

        // Limiter le nombre total de suggestions
        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Recherche dans les biens
     */
    private function searchProperties(string $query, $user, int $limit): array
    {
        $qb = $this->propertyRepo->createQueryBuilder('prop')
            ->where('prop.address LIKE :query')
            ->orWhere('prop.city LIKE :query')
            ->orWhere('prop.postalCode LIKE :query')
            ->orWhere('prop.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit);

        // Log pour debug
        error_log("GlobalSearch - searchProperties - Query: '$query', User: " . ($user ? $user->getEmail() : 'null'));

        // Filtrage multi-tenant
        $qb = $this->applyMultiTenantFilter($qb, 'prop', $user);

        // Log de la requête SQL générée
        $sql = $qb->getQuery()->getSQL();
        error_log("GlobalSearch - searchProperties - SQL: " . $sql);

        $results = $qb->getQuery()->getResult();
        error_log("GlobalSearch - searchProperties - Results count: " . count($results));

        return $results;
    }

    /**
     * Recherche dans les locataires
     */
    private function searchTenants(string $query, $user, int $limit): array
    {
        $qb = $this->tenantRepo->createQueryBuilder('t')
            ->where('t.firstName LIKE :query')
            ->orWhere('t.lastName LIKE :query')
            ->orWhere('CONCAT(t.firstName, \' \', t.lastName) LIKE :query')
            ->orWhere('t.email LIKE :query')
            ->orWhere('t.phone LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit);

        // Log pour debug
        error_log("GlobalSearch - searchTenants - Query: '$query', User: " . ($user ? $user->getEmail() : 'null'));

        // Filtrage multi-tenant
        $qb = $this->applyMultiTenantFilter($qb, 't', $user);

        // Log de la requête SQL générée
        $sql = $qb->getQuery()->getSQL();
        error_log("GlobalSearch - searchTenants - SQL: " . $sql);

        $results = $qb->getQuery()->getResult();
        error_log("GlobalSearch - searchTenants - Results count: " . count($results));

        return $results;
    }

    /**
     * Recherche dans les baux
     */
    private function searchLeases(string $query, $user, int $limit): array
    {
        $qb = $this->leaseRepo->createQueryBuilder('l')
            ->leftJoin('l.tenant', 't')
            ->leftJoin('l.property', 'p')
            ->where('t.firstName LIKE :query')
            ->orWhere('t.lastName LIKE :query')
            ->orWhere('CONCAT(t.firstName, \' \', t.lastName) LIKE :query')
            ->orWhere('p.address LIKE :query')
            ->orWhere('l.status LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit);

        // Log pour debug
        error_log("GlobalSearch - searchLeases - Query: '$query', User: " . ($user ? $user->getEmail() : 'null'));

        // Filtrage multi-tenant
        $qb = $this->applyMultiTenantFilter($qb, 'l', $user);

        // Log de la requête SQL générée
        $sql = $qb->getQuery()->getSQL();
        error_log("GlobalSearch - searchLeases - SQL: " . $sql);

        $results = $qb->getQuery()->getResult();
        error_log("GlobalSearch - searchLeases - Results count: " . count($results));

        return $results;
    }

    /**
     * Recherche dans les paiements
     */
    private function searchPayments(string $query, $user, int $limit): array
    {
        $qb = $this->paymentRepo->createQueryBuilder('pay')
            ->leftJoin('pay.lease', 'l')
            ->leftJoin('l.tenant', 't')
            ->where('t.firstName LIKE :query')
            ->orWhere('t.lastName LIKE :query')
            ->orWhere('CONCAT(t.firstName, \' \', t.lastName) LIKE :query')
            ->orWhere('pay.status LIKE :query')
            ->orWhere('pay.amount LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit);

        // Log pour debug
        error_log("GlobalSearch - searchPayments - Query: '$query', User: " . ($user ? $user->getEmail() : 'null'));

        // Filtrage multi-tenant
        $qb = $this->applyMultiTenantFilter($qb, 'pay', $user);

        // Log de la requête SQL générée
        $sql = $qb->getQuery()->getSQL();
        error_log("GlobalSearch - searchPayments - SQL: " . $sql);

        $results = $qb->getQuery()->getResult();
        error_log("GlobalSearch - searchPayments - Results count: " . count($results));

        return $results;
    }

    /**
     * Recherche dans les documents
     */
    private function searchDocuments(string $query, $user, int $limit): array
    {
        $qb = $this->documentRepo->createQueryBuilder('d')
            ->where('d.name LIKE :query')
            ->orWhere('d.type LIKE :query')
            ->orWhere('d.description LIKE :query')
            ->orWhere('d.originalFileName LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit);

        // Filtrage multi-tenant
        $qb = $this->applyMultiTenantFilter($qb, 'd', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche dans les demandes de maintenance
     */
    private function searchMaintenance(string $query, $user, int $limit): array
    {
        $qb = $this->maintenanceRepo->createQueryBuilder('m')
            ->leftJoin('m.property', 'p')
            ->where('m.description LIKE :query')
            ->orWhere('m.status LIKE :query')
            ->orWhere('p.address LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults($limit);

        // Filtrage multi-tenant
        $qb = $this->applyMultiTenantFilter($qb, 'm', $user);

        return $qb->getQuery()->getResult();
    }

    /**
     * Applique le filtrage multi-tenant selon le rôle
     */
    private function applyMultiTenantFilter($qb, string $alias, $user)
    {
        if (!$user) {
            // Pas d'utilisateur = pas de résultats
            error_log("GlobalSearch - applyMultiTenantFilter - No user, blocking results");
            $qb->andWhere('1 = 0');
            return $qb;
        }

        $roles = $user->getRoles();
        error_log("GlobalSearch - applyMultiTenantFilter - User: " . $user->getEmail() . ", Roles: " . implode(', ', $roles) . ", Alias: $alias");

        // ========================================
        // ROLE_TENANT : Voir UNIQUEMENT ses données
        // ========================================
        if (in_array('ROLE_TENANT', $roles)) {
            $tenant = $user->getTenant();
            if (!$tenant) {
                error_log("GlobalSearch - applyMultiTenantFilter - ROLE_TENANT but no tenant profile, blocking results");
                $qb->andWhere('1 = 0'); // Pas de tenant = aucun résultat
                return $qb;
            }

            error_log("GlobalSearch - applyMultiTenantFilter - ROLE_TENANT, tenant: " . $tenant->getFullName());

            // Adapter selon l'entité
            switch ($alias) {
                case 'prop': // Property - via lease
                    error_log("GlobalSearch - applyMultiTenantFilter - Filtering properties via leases for tenant");
                    $qb->leftJoin('prop.leases', 'prop_leases')
                       ->andWhere('prop_leases.tenant = :tenant')
                       ->setParameter('tenant', $tenant);
                    break;

                case 't': // Tenant - uniquement lui-même
                    $qb->andWhere('t.id = :tenantId')
                       ->setParameter('tenantId', $tenant->getId());
                    break;

                case 'l': // Lease - ses baux
                    $qb->andWhere('l.tenant = :tenant')
                       ->setParameter('tenant', $tenant);
                    break;

                case 'pay': // Payment - via lease
                    $qb->andWhere('l.tenant = :tenant')
                       ->setParameter('tenant', $tenant);
                    break;

                case 'd': // Document
                    $qb->andWhere('d.tenant = :tenant')
                       ->setParameter('tenant', $tenant);
                    break;

                case 'm': // Maintenance - via property puis lease
                    $qb->andWhere('m.tenant = :tenant OR m.createdBy = :tenantUser')
                       ->setParameter('tenant', $tenant)
                       ->setParameter('tenantUser', $user);
                    break;
            }
        }
        // ========================================
        // ROLE_MANAGER : Voir SES propriétés (via organization/company)
        // ========================================
        elseif (in_array('ROLE_MANAGER', $roles)) {
            $owner = $user->getOwner();
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            if (!$owner && !$organization && !$company) {
                $qb->andWhere('1 = 0'); // Pas d'owner/organization/company = aucun résultat
                return $qb;
            }

            // Filtrage par owner, organization ou company
            switch ($alias) {
                case 'prop': // Property
                    if ($company) {
                        $qb->andWhere('prop.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('prop.organization = :organization')
                           ->setParameter('organization', $organization);
                    } elseif ($owner) {
                        $qb->andWhere('prop.owner = :owner')
                           ->setParameter('owner', $owner);
                    }
                    break;

                case 't': // Tenant via property
                    $qb->leftJoin('t.leases', 't_leases')
                       ->leftJoin('t_leases.property', 't_property');
                    if ($company) {
                        $qb->andWhere('t_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('t_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    } elseif ($owner) {
                        $qb->andWhere('t_property.owner = :owner')
                           ->setParameter('owner', $owner);
                    }
                    break;

                case 'l': // Lease via property
                    $qb->leftJoin('l.property', 'l_property');
                    if ($company) {
                        $qb->andWhere('l_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('l_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    } elseif ($owner) {
                        $qb->andWhere('l_property.owner = :owner')
                           ->setParameter('owner', $owner);
                    }
                    break;

                case 'pay': // Payment via lease puis property
                    $qb->leftJoin('l.property', 'pay_property');
                    if ($company) {
                        $qb->andWhere('pay_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('pay_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    } elseif ($owner) {
                        $qb->andWhere('pay_property.owner = :owner')
                           ->setParameter('owner', $owner);
                    }
                    break;

                case 'd': // Document via property
                    $qb->leftJoin('d.property', 'd_property');
                    if ($company) {
                        $qb->andWhere('d_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('d_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    } elseif ($owner) {
                        $qb->andWhere('d_property.owner = :owner')
                           ->setParameter('owner', $owner);
                    }
                    break;

                case 'm': // Maintenance via property
                    $qb->leftJoin('m.property', 'm_property');
                    if ($company) {
                        $qb->andWhere('m_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('m_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    } elseif ($owner) {
                        $qb->andWhere('m_property.owner = :owner')
                           ->setParameter('owner', $owner);
                    }
                    break;
            }
        }
        // ========================================
        // ROLE_ADMIN / ROLE_SUPER_ADMIN : Filtrer par Organization/Company
        // ========================================
        elseif (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_SUPER_ADMIN', $roles)) {
            $organization = $user->getOrganization();
            $company = $user->getCompany();

            error_log("GlobalSearch - applyMultiTenantFilter - ADMIN/SUPER_ADMIN, organization: " . ($organization ? $organization->getName() : 'null') . ", company: " . ($company ? $company->getName() : 'null'));

            // Adapter selon l'entité
            switch ($alias) {
                case 'prop': // Property - accès direct aux champs
                    if ($company) {
                        error_log("GlobalSearch - applyMultiTenantFilter - Filtering properties by company: " . $company->getName());
                        $qb->andWhere('prop.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        error_log("GlobalSearch - applyMultiTenantFilter - Filtering properties by organization: " . $organization->getName());
                        $qb->andWhere('prop.organization = :organization')
                           ->setParameter('organization', $organization);
                    }
                    break;

                case 't': // Tenant - via property
                    $qb->leftJoin('t.leases', 't_leases')
                       ->leftJoin('t_leases.property', 't_property');
                    if ($company) {
                        $qb->andWhere('t_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('t_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    }
                    break;

                case 'l': // Lease - via property
                    $qb->leftJoin('l.property', 'l_property');
                    if ($company) {
                        $qb->andWhere('l_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('l_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    }
                    break;

                case 'pay': // Payment - via lease puis property
                    $qb->leftJoin('pay.lease', 'pay_lease')
                       ->leftJoin('pay_lease.property', 'pay_property');
                    if ($company) {
                        $qb->andWhere('pay_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('pay_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    }
                    break;

                case 'd': // Document - via property
                    $qb->leftJoin('d.property', 'd_property');
                    if ($company) {
                        $qb->andWhere('d_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('d_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    }
                    break;

                case 'm': // Maintenance - via property
                    $qb->leftJoin('m.property', 'm_property');
                    if ($company) {
                        $qb->andWhere('m_property.company = :company')
                           ->setParameter('company', $company);
                    } elseif ($organization) {
                        $qb->andWhere('m_property.organization = :organization')
                           ->setParameter('organization', $organization);
                    }
                    break;
            }

            // SUPER_ADMIN sans organization/company : voir TOUT (pas de filtre)
            if (!$company && !$organization) {
                error_log("GlobalSearch - applyMultiTenantFilter - SUPER_ADMIN without organization/company, no filter applied");
            }
        }
        // ========================================
        // Autres rôles : Aucun accès
        // ========================================
        else {
            $qb->andWhere('1 = 0'); // Pas de rôle reconnu = aucun résultat
        }

        return $qb;
    }

    /**
     * Formate un résultat pour autocomplete
     */
    private function formatForAutocomplete($item, string $entityType): array
    {
        $formatted = [
            'type' => $entityType,
            'id' => $item->getId(),
        ];

        switch ($entityType) {
            case 'properties':
                $formatted['title'] = $item->getAddress();
                $subtitle = '';
                if (method_exists($item, 'getCity') && $item->getCity()) {
                    $subtitle = $item->getCity();
                }
                if (method_exists($item, 'getPostalCode') && $item->getPostalCode()) {
                    $subtitle .= ' ' . $item->getPostalCode();
                }
                $formatted['subtitle'] = $subtitle ?: 'Bien immobilier';
                $formatted['icon'] = 'bi-building';
                $formatted['url'] = '/mes-biens/' . $item->getId();
                if (method_exists($item, 'getStatus')) {
                    $formatted['badge'] = $item->getStatus();
                }
                break;

            case 'tenants':
                $formatted['title'] = $item->getFirstName() . ' ' . $item->getLastName();
                $formatted['subtitle'] = $item->getEmail();
                $formatted['icon'] = 'bi-person';
                $formatted['url'] = '/locataires/' . $item->getId();
                break;

            case 'leases':
                $tenant = $item->getTenant();
                $property = $item->getProperty();
                $formatted['title'] = 'Bail #' . $item->getId();
                $formatted['subtitle'] = $tenant ? $tenant->getFullName() : 'N/A';
                $formatted['subtitle'] .= $property ? ' - ' . $property->getAddress() : '';
                $formatted['icon'] = 'bi-file-text';
                $formatted['url'] = '/contrats/' . $item->getId();
                $formatted['badge'] = $item->getStatus();
                break;

            case 'payments':
                $lease = $item->getLease();
                $tenant = $lease ? $lease->getTenant() : null;
                $formatted['title'] = number_format($item->getAmount(), 0, ',', ' ') . ' FCFA';
                $formatted['subtitle'] = $tenant ? $tenant->getFullName() : 'N/A';
                $formatted['subtitle'] .= ' - ' . $item->getDueDate()->format('d/m/Y');
                $formatted['icon'] = 'bi-cash';
                $formatted['url'] = '/mes-paiements';
                $formatted['badge'] = $item->getStatus();
                break;

            case 'documents':
                $formatted['title'] = $item->getName();
                $formatted['subtitle'] = $item->getType();
                $formatted['icon'] = 'bi-file-earmark';
                $formatted['url'] = '/mes-documents/' . $item->getId();
                break;

            case 'maintenance':
                $property = $item->getProperty();
                $formatted['title'] = method_exists($item, 'getTitle') ? $item->getTitle() : 'Maintenance #' . $item->getId();
                $formatted['subtitle'] = $property ? $property->getAddress() : 'N/A';
                $formatted['icon'] = 'bi-tools';
                $formatted['url'] = '/mes-demandes/' . $item->getId();
                $formatted['badge'] = $item->getStatus();
                break;
        }

        return $formatted;
    }

    /**
     * Statistiques de recherche
     */
    public function getSearchStats(array $results): array
    {
        return [
            'total' => array_sum(array_map('count', $results)),
            'by_type' => array_map('count', $results),
        ];
    }
}

