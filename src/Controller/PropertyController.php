<?php

namespace App\Controller;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/properties')]
class PropertyController extends AbstractController
{
    #[Route('/', name: 'app_property_index', methods: ['GET'])]
    public function index(PropertyRepository $propertyRepository, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $status = $request->query->get('status', '');
        
        $criteria = [];
        if ($search) {
            $criteria['city'] = $search;
        }
        if ($status) {
            $criteria['status'] = $status;
        }

        if (!empty($criteria)) {
            $properties = $propertyRepository->findByCriteria($criteria);
        } else {
            $properties = $propertyRepository->findAllWithCurrentStatus();
        }

        return $this->render('property/index.html.twig', [
            'properties' => $properties,
            'search' => $search,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_property_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $property = new Property();
        
        if ($request->isMethod('POST')) {
            $property->setAddress($request->get('address'));
            $property->setCity($request->get('city'));
            $property->setPostalCode($request->get('postal_code'));
            $property->setPropertyType($request->get('property_type'));
            $property->setSurface((float)$request->get('surface'));
            $property->setRooms((int)$request->get('rooms'));
            $property->setMonthlyRent($request->get('monthly_rent'));
            $property->setCharges($request->get('charges'));
            $property->setDeposit($request->get('deposit'));
            $property->setDescription($request->get('description'));
            $property->setStatus($request->get('status', 'Libre'));

            $entityManager->persist($property);
            $entityManager->flush();

            $this->addFlash('success', 'Propriété créée avec succès !');
            return $this->redirectToRoute('app_property_index');
        }

        return $this->render('property/new.html.twig', [
            'property' => $property,
        ]);
    }

    #[Route('/{id}', name: 'app_property_show', methods: ['GET'])]
    public function show(Property $property): Response
    {
        $currentLease = $property->getCurrentLease();
        
        return $this->render('property/show.html.twig', [
            'property' => $property,
            'currentLease' => $currentLease,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_property_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $property->setAddress($request->get('address'));
            $property->setCity($request->get('city'));
            $property->setPostalCode($request->get('postal_code'));
            $property->setPropertyType($request->get('property_type'));
            $property->setSurface((float)$request->get('surface'));
            $property->setRooms((int)$request->get('rooms'));
            $property->setMonthlyRent($request->get('monthly_rent'));
            $property->setCharges($request->get('charges'));
            $property->setDeposit($request->get('deposit'));
            $property->setDescription($request->get('description'));
            $property->setStatus($request->get('status'));
            $property->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Propriété modifiée avec succès !');
            return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
        }

        return $this->render('property/edit.html.twig', [
            'property' => $property,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_property_delete', methods: ['POST'])]
    public function delete(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$property->getId(), $request->request->get('_token'))) {
            $entityManager->remove($property);
            $entityManager->flush();
            $this->addFlash('success', 'Propriété supprimée avec succès !');
        }

        return $this->redirectToRoute('app_property_index');
    }
}