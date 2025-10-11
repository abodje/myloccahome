<?php

namespace App\Controller;

use App\Entity\Property;
use App\Repository\PropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/property')]
class PropertyController extends AbstractController
{
    #[Route('/', name: 'app_property_index', methods: ['GET'])]
    public function index(PropertyRepository $propertyRepository, Request $request): Response
    {
        $search = $request->query->get('search');
        $type = $request->query->get('type');
        $available = $request->query->get('available');

        if ($search) {
            $properties = $propertyRepository->findByCity($search);
        } elseif ($type) {
            $properties = $propertyRepository->findByType($type);
        } elseif ($available !== null) {
            $properties = $available ? $propertyRepository->findAvailableProperties() : $propertyRepository->findBy(['available' => false]);
        } else {
            $properties = $propertyRepository->findAll();
        }

        return $this->render('property/index.html.twig', [
            'properties' => $properties,
            'search' => $search,
            'type' => $type,
            'available' => $available,
        ]);
    }

    #[Route('/new', name: 'app_property_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $property = new Property();

        if ($request->isMethod('POST')) {
            $property->setTitle($request->request->get('title'));
            $property->setDescription($request->request->get('description'));
            $property->setAddress($request->request->get('address'));
            $property->setCity($request->request->get('city'));
            $property->setPostalCode($request->request->get('postal_code'));
            $property->setCountry($request->request->get('country'));
            $property->setType($request->request->get('type'));
            $property->setSurface((int)$request->request->get('surface'));
            $property->setRooms((int)$request->request->get('rooms'));
            $property->setBedrooms((int)$request->request->get('bedrooms'));
            $property->setBathrooms((int)$request->request->get('bathrooms'));
            $property->setMonthlyRent($request->request->get('monthly_rent'));
            $property->setCharges($request->request->get('charges'));
            $property->setDeposit($request->request->get('deposit'));
            $property->setFurnished($request->request->get('furnished') === '1');

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
        return $this->render('property/show.html.twig', [
            'property' => $property,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_property_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Property $property, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $property->setTitle($request->request->get('title'));
            $property->setDescription($request->request->get('description'));
            $property->setAddress($request->request->get('address'));
            $property->setCity($request->request->get('city'));
            $property->setPostalCode($request->request->get('postal_code'));
            $property->setCountry($request->request->get('country'));
            $property->setType($request->request->get('type'));
            $property->setSurface((int)$request->request->get('surface'));
            $property->setRooms((int)$request->request->get('rooms'));
            $property->setBedrooms((int)$request->request->get('bedrooms'));
            $property->setBathrooms((int)$request->request->get('bathrooms'));
            $property->setMonthlyRent($request->request->get('monthly_rent'));
            $property->setCharges($request->request->get('charges'));
            $property->setDeposit($request->request->get('deposit'));
            $property->setFurnished($request->request->get('furnished') === '1');
            $property->setAvailable($request->request->get('available') === '1');
            $property->setUpdatedAt(new \DateTime());

            $entityManager->flush();

            $this->addFlash('success', 'Propriété modifiée avec succès !');
            return $this->redirectToRoute('app_property_show', ['id' => $property->getId()]);
        }

        return $this->render('property/edit.html.twig', [
            'property' => $property,
        ]);
    }

    #[Route('/{id}', name: 'app_property_delete', methods: ['POST'])]
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