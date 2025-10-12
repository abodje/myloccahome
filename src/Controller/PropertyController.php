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
            // Si l'utilisateur est un gestionnaire, montrer ses propriétés
            $owner = $user->getOwner();
            if ($owner) {
                $properties = $propertyRepository->findByOwnerWithFilters($owner->getId(), $search, $status, $type);
            } else {
                $properties = $propertyRepository->findWithFilters($search, $status, $type);
            }
        } else {
            // Pour les admins, montrer toutes les propriétés
            $properties = $propertyRepository->findWithFilters($search, $status, $type);
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
    public function documents(Property $property): Response
    {
        return $this->render('property/documents.html.twig', [
            'property' => $property,
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
            'surface_total' => $property->getSurface(),
            'surface_carrez' => $property->getSurface(), // Même valeur pour l'exemple
            'rooms' => $property->getRooms(),
            'lot_number' => str_pad($property->getId(), 3, '0', STR_PAD_LEFT),
            'property_type' => $property->getPropertyType(),
            'description' => $property->getDescription(),
            'status' => $property->getStatus(),
            // Informations supplémentaires simulées
            'gardien' => 'Oui',
            'ascenseur' => 'Oui',
            'jours_gardien' => 'lundi au vendredi, samedi matin',
            'horaires_gardien' => '8h-12h, 16h00-19h30, sam 7h-12',
            'digicode_bat1' => 'CHAUF 6905',
            'digicode_entree' => '0169portail',
        ];

        return $this->json($data);
    }
}
