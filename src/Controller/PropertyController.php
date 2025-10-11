<?php

namespace App\Controller;

use App\Entity\Property;
use App\Form\PropertyType;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/properties')]
class PropertyController extends AbstractController
{
    #[Route('/', name: 'app_property_index', methods: ['GET'])]
    public function index(Request $request, PropertyRepository $propertyRepository): Response
    {
        $search = $request->query->get('search');
        $city = $request->query->get('city');
        $minRent = $request->query->get('min_rent') ? (float)$request->query->get('min_rent') : null;
        $maxRent = $request->query->get('max_rent') ? (float)$request->query->get('max_rent') : null;
        $status = $request->query->get('status');

        if ($search || $city || $minRent || $maxRent) {
            $properties = $propertyRepository->searchProperties($search, $city, $minRent, $maxRent);
        } else {
            $properties = $propertyRepository->findAll();
        }

        if ($status) {
            $properties = array_filter($properties, fn($property) => $property->getStatus() === $status);
        }

        return $this->render('property/index.html.twig', [
            'properties' => $properties,
            'search' => $search,
            'city' => $city,
            'min_rent' => $minRent,
            'max_rent' => $maxRent,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_property_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $property = new Property();
        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $property->setStatus('available'); // Par défaut
            $entityManager->persist($property);
            $entityManager->flush();

            $this->addFlash('success', 'La propriété a été créée avec succès.');

            return $this->redirectToRoute('app_property_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('property/new.html.twig', [
            'property' => $property,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_property_show', methods: ['GET'])]
    public function show(Property $property): Response
    {
        $currentContract = $property->getCurrentContract();
        $maintenances = $property->getMaintenances()->toArray();
        
        // Trier les maintenances par date de création (plus récentes en premier)
        usort($maintenances, fn($a, $b) => $b->getCreatedAt() <=> $a->getCreatedAt());

        return $this->render('property/show.html.twig', [
            'property' => $property,
            'currentContract' => $currentContract,
            'maintenances' => array_slice($maintenances, 0, 10), // 10 dernières maintenances
        ]);
    }

    #[Route('/{id}/edit', name: 'app_property_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PropertyType::class, $property);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $property->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'La propriété a été modifiée avec succès.');

            return $this->redirectToRoute('app_property_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('property/edit.html.twig', [
            'property' => $property,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_property_delete', methods: ['POST'])]
    public function delete(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->getPayload()->getString('_token'))) {
            // Vérifier qu'il n'y a pas de contrat actif
            if ($property->getCurrentContract()) {
                $this->addFlash('error', 'Impossible de supprimer une propriété avec un contrat actif.');
            } else {
                $entityManager->remove($property);
                $entityManager->flush();
                $this->addFlash('success', 'La propriété a été supprimée avec succès.');
            }
        }

        return $this->redirectToRoute('app_property_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/available', name: 'app_property_available', methods: ['GET'])]
    public function available(PropertyRepository $propertyRepository): Response
    {
        $properties = $propertyRepository->findAvailable();

        return $this->render('property/available.html.twig', [
            'properties' => $properties,
        ]);
    }

    #[Route('/occupied', name: 'app_property_occupied', methods: ['GET'])]
    public function occupied(PropertyRepository $propertyRepository): Response
    {
        $properties = $propertyRepository->findOccupied();

        return $this->render('property/occupied.html.twig', [
            'properties' => $properties,
        ]);
    }

    #[Route('/{id}/change-status', name: 'app_property_change_status', methods: ['POST'])]
    public function changeStatus(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('status');
        
        if (in_array($newStatus, ['available', 'occupied', 'maintenance', 'unavailable'])) {
            $property->setStatus($newStatus);
            $property->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le statut de la propriété a été mis à jour.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
    }
}