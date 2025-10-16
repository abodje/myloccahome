<?php

namespace App\Controller;

use App\Entity\Property;
use App\Form\PropertyType;
use App\Repository\PropertyRepository;
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mes-biens')]
class PropertyController extends AbstractController
{
    #[Route('/', name: 'app_property_index', methods: ['GET'])]
    public function index(PropertyRepository $propertyRepository, Request $request): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        $search = $request->query->get('search');
        $status = $request->query->get('status');
        $type = $request->query->get('type');

        // Filtrer les propriétés selon le rôle de l'utilisateur
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            // Si l'utilisateur est un locataire, ne montrer que les propriétés qu'il loue
            $tenant = $user->getTenant();
            if ($tenant) {
                $properties = $propertyRepository->findByTenantWithFilters($tenant->getId(), $search, $status, $type);
            } else {
                $properties = [];
            }
        } elseif ($user && in_array('ROLE_MANAGER', $user->getRoles())) {
            // Si l'utilisateur est un gestionnaire, montrer les propriétés de SA société
            $company = method_exists($user, 'getCompany') ? $user->getCompany() : null;
            if ($company) {
                // Filtrer par company
                $properties = $propertyRepository->createQueryBuilder('p')
                    ->where('p.company = :company')
                    ->setParameter('company', $company)
                    ->orderBy('p.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            } else {
                // Fallback: filtrer par owner (ancien système)
                $owner = $user->getOwner();
                if ($owner) {
                    $properties = $propertyRepository->findByOwnerWithFilters($owner->getId(), $search, $status, $type);
                } else {
                    $properties = [];
                }
            }
        } else {
            // Pour les admins, filtrer par ORGANIZATION uniquement
            if ($user && method_exists($user, 'getOrganization') && $user->getOrganization()) {
                $organization = $user->getOrganization();
                $properties = $propertyRepository->createQueryBuilder('p')
                    ->where('p.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orderBy('p.createdAt', 'DESC')
                    ->getQuery()
                    ->getResult();
            } else {
                // Fallback si pas d'organization
                $properties = $propertyRepository->findWithFilters($search, $status, $type);
            }
        }

        // Passer une variable pour indiquer si c'est la vue locataire
        $isTenantView = $user && in_array('ROLE_TENANT', $user->getRoles());

        return $this->render('property/index.html.twig', [
            'properties' => $properties,
            'search' => $search,
            'current_status' => $status,
            'current_type' => $type,
            'is_tenant_view' => $isTenantView,
        ]);
    }

    #[Route('/nouveau', name: 'app_property_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur n'est pas un locataire
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            throw $this->createAccessDeniedException('Les locataires ne peuvent pas ajouter de biens.');
        }

        $property = new Property();

        // Auto-assigner organization et company
        if ($user && method_exists($user, 'getOrganization') && $user->getOrganization()) {
            $property->setOrganization($user->getOrganization());

            // Si l'utilisateur a une company assignée, l'utiliser
            if (method_exists($user, 'getCompany') && $user->getCompany()) {
                $property->setCompany($user->getCompany());
            } else {
                // Sinon, utiliser la company par défaut (siège social)
                $headquarter = $user->getOrganization()->getHeadquarterCompany();
                if ($headquarter) {
                    $property->setCompany($headquarter);
                }
            }
        }

        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($property);
            $entityManager->flush();

            $this->addFlash('success', 'La propriété a été créée avec succès.');

            return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
        }

        return $this->render('property/new.html.twig', [
            'property' => $property,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_property_show', methods: ['GET'])]
    public function show(Property $property): Response
    {
        $currentLease = $property->getCurrentLease();
        $leases = $property->getLeases();

        return $this->render('property/show.html.twig', [
            'property' => $property,
            'current_lease' => $currentLease,
            'leases' => $leases,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_property_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur n'est pas un locataire
        if ($user && in_array('ROLE_TENANT', $user->getRoles())) {
            throw $this->createAccessDeniedException('Les locataires ne peuvent pas modifier de biens.');
        }

        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $property->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'La propriété a été modifiée avec succès.');

            return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
        }

        return $this->render('property/edit.html.twig', [
            'property' => $property,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_property_delete', methods: ['POST'])]
    public function delete(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->getPayload()->getString('_token'))) {
            // Vérifier qu'il n'y a pas de contrat actif
            if ($property->getCurrentLease()) {
                $this->addFlash('error', 'Impossible de supprimer une propriété avec un contrat actif.');
                return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
            }

            $entityManager->remove($property);
            $entityManager->flush();

            $this->addFlash('success', 'La propriété a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_property_index');
    }

    #[Route('/{id}/documents', name: 'app_property_documents', methods: ['GET'])]
    public function documents(Property $property, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les documents liés à cette propriété
        $documents = $entityManager->getRepository(\App\Entity\Document::class)
            ->findBy(['property' => $property], ['createdAt' => 'DESC']);

        return $this->render('property/documents.html.twig', [
            'property' => $property,
            'documents' => $documents,
        ]);
    }

    #[Route('/{id}/maintenance', name: 'app_property_maintenance', methods: ['GET'])]
    public function maintenance(Property $property): Response
    {
        return $this->render('property/maintenance.html.twig', [
            'property' => $property,
        ]);
    }

    #[Route('/{id}/inventaires', name: 'app_property_inventories', methods: ['GET'])]
    public function inventories(Property $property, InventoryRepository $inventoryRepository): Response
    {
        $inventories = $inventoryRepository->findBy(['property' => $property], ['inventoryDate' => 'DESC']);

        return $this->render('property/inventories.html.twig', [
            'property' => $property,
            'inventories' => $inventories,
        ]);
    }

    #[Route('/api/{id}/details', name: 'app_property_api_details', methods: ['GET'])]
    public function apiDetails(Property $property): Response
    {
        $data = [
            'id' => $property->getId(),
            'address' => $property->getFullAddress(),
            'city' => $property->getCity(),
            'postalCode' => $property->getPostalCode(),
            'country' => $property->getCountry(),
            'region' => $property->getRegion(),
            'district' => $property->getDistrict(),
            'latitude' => $property->getLatitude(),
            'longitude' => $property->getLongitude(),

            // Caractéristiques physiques
            'surface' => $property->getSurface(),
            'rooms' => $property->getRooms(),
            'bedrooms' => $property->getBedrooms(),
            'bathrooms' => $property->getBathrooms(),
            'toilets' => $property->getToilets(),
            'floor' => $property->getFloor(),
            'totalFloors' => $property->getTotalFloors(),
            'balconies' => $property->getBalconies(),
            'terraceSurface' => $property->getTerraceSurface(),
            'gardenSurface' => $property->getGardenSurface(),
            'parkingSpaces' => $property->getParkingSpaces(),
            'garageSpaces' => $property->getGarageSpaces(),
            'cellarSurface' => $property->getCellarSurface(),
            'atticSurface' => $property->getAtticSurface(),
            'landSurface' => $property->getLandSurface(),

            // Informations de construction
            'constructionYear' => $property->getConstructionYear(),
            'renovationYear' => $property->getRenovationYear(),
            'heatingType' => $property->getHeatingType(),
            'hotWaterType' => $property->getHotWaterType(),
            'energyClass' => $property->getEnergyClass(),
            'energyConsumption' => $property->getEnergyConsumption(),
            'orientation' => $property->getOrientation(),

            // Informations financières
            'monthlyRent' => $property->getMonthlyRent(),
            'charges' => $property->getCharges(),
            'deposit' => $property->getDeposit(),
            'purchasePrice' => $property->getPurchasePrice(),
            'purchaseDate' => $property->getPurchaseDate() ? $property->getPurchaseDate()->format('d/m/Y') : null,
            'estimatedValue' => $property->getEstimatedValue(),
            'monthlyCharges' => $property->getMonthlyCharges(),
            'propertyTax' => $property->getPropertyTax(),
            'insurance' => $property->getInsurance(),
            'maintenanceBudget' => $property->getMaintenanceBudget(),

            // Informations d'accès
            'keyLocation' => $property->getKeyLocation(),
            'accessCode' => $property->getAccessCode(),
            'intercom' => $property->getIntercom(),

            // Descriptions
            'propertyType' => $property->getPropertyType(),
            'description' => $property->getDescription(),
            'equipment' => $property->getEquipment(),
            'proximity' => $property->getProximity(),
            'restrictions' => $property->getRestrictions(),
            'notes' => $property->getNotes(),

            // Statut et propriétaire
            'status' => $property->getStatus(),
            'owner' => $property->getOwner() ? $property->getOwner()->getFullName() : null,

            // Équipements booléens
            'furnished' => $property->isFurnished(),
            'petsAllowed' => $property->isPetsAllowed(),
            'smokingAllowed' => $property->isSmokingAllowed(),
            'elevator' => $property->isElevator(),
            'hasBalcony' => $property->isHasBalcony(),
            'hasParking' => $property->isHasParking(),
            'airConditioning' => $property->isAirConditioning(),
            'heating' => $property->isHeating(),
            'hotWater' => $property->isHotWater(),
            'internet' => $property->isInternet(),
            'cable' => $property->isCable(),
            'dishwasher' => $property->isDishwasher(),
            'washingMachine' => $property->isWashingMachine(),
            'dryer' => $property->isDryer(),
            'refrigerator' => $property->isRefrigerator(),
            'oven' => $property->isOven(),
            'microwave' => $property->isMicrowave(),
            'stove' => $property->isStove(),

            // Méthodes utilitaires
            'totalSurface' => $property->getTotalSurface(),
            'rentWithCharges' => $property->getRentWithCharges(),
            'totalRooms' => $property->getTotalRooms(),
            'equipmentList' => $property->getEquipmentList(),
            'fullLocation' => $property->getFullLocation(),

            // Locataire actuel
            'currentLease' => $property->getCurrentLease() ? [
                'id' => $property->getCurrentLease()->getId(),
                'tenant' => $property->getCurrentLease()->getTenant() ? $property->getCurrentLease()->getTenant()->getFullName() : null,
                'startDate' => $property->getCurrentLease()->getStartDate() ? $property->getCurrentLease()->getStartDate()->format('d/m/Y') : null,
                'endDate' => $property->getCurrentLease()->getEndDate() ? $property->getCurrentLease()->getEndDate()->format('d/m/Y') : null,
            ] : null,

            // Dates
            'createdAt' => $property->getCreatedAt() ? $property->getCreatedAt()->format('d/m/Y H:i') : null,
            'updatedAt' => $property->getUpdatedAt() ? $property->getUpdatedAt()->format('d/m/Y H:i') : null,
        ];

        return $this->json($data);
    }
}
