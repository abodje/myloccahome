<?php

namespace App\Controller;

use App\Entity\Owner;
use App\Form\OwnerType;
use App\Repository\OwnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/proprietaires')]
class OwnerController extends AbstractController
{
    #[Route('/', name: 'app_owner_index', methods: ['GET'])]
    public function index(OwnerRepository $ownerRepository, Request $request): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $search = $request->query->get('search');
        $type = $request->query->get('type');

        // Filtrer par organisation et société de l'utilisateur
        $organization = $user ? $user->getOrganization() : null;
        $company = $user ? $user->getCompany() : null;

        $owners = $ownerRepository->findAllFiltered($organization, $company, $search, $type);

        // Calculer les statistiques à partir des propriétaires filtrés
        $stats = [
            'total' => count($owners),
            'particuliers' => count(array_filter($owners, fn($o) => $o->getOwnerType() === 'Particulier')),
            'societes' => count(array_filter($owners, fn($o) => $o->getOwnerType() !== 'Particulier')),
        ];

        return $this->render('owner/index.html.twig', [
            'owners' => $owners,
            'stats' => $stats,
            'current_search' => $search,
            'current_type' => $type,
        ]);
    }

    #[Route('/nouveau', name: 'app_owner_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();

        $owner = new Owner();

        // Assigner automatiquement l'organisation et la société de l'utilisateur
        if ($user) {
            $owner->setOrganization($user->getOrganization());
            $owner->setCompany($user->getCompany());
        }

        $form = $this->createForm(OwnerType::class, $owner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($owner);
                $entityManager->flush();

                $this->addFlash('success', 'Le propriétaire a été créé avec succès.');

                return $this->redirectToRoute('app_owner_show', ['id' => $owner->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du propriétaire : ' . $e->getMessage());
            }
        }

        return $this->render('owner/new.html.twig', [
            'owner' => $owner,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_owner_show', methods: ['GET'])]
    public function show(Owner $owner): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        // Vérifier l'accès au propriétaire selon l'organisation/société
        if ($user && !$this->canAccessOwner($user, $owner)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce propriétaire.');
        }

        // Calculer les statistiques du propriétaire
        $stats = [
            'total_properties' => $owner->getProperties()->count(),
            'active_properties' => $owner->getActivePropertiesCount(),
            'total_monthly_rent' => $owner->getTotalMonthlyRent(),
            'available_properties' => 0,
        ];

        // Compter les propriétés disponibles
        foreach ($owner->getProperties() as $property) {
            if ($property->getStatus() === 'Libre') {
                $stats['available_properties']++;
            }
        }

        return $this->render('owner/show.html.twig', [
            'owner' => $owner,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_owner_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Owner $owner, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        // Vérifier l'accès au propriétaire selon l'organisation/société
        if ($user && !$this->canAccessOwner($user, $owner)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce propriétaire.');
        }

        $form = $this->createForm(OwnerType::class, $owner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $owner->setUpdatedAt(new \DateTime());
                $entityManager->flush();

                $this->addFlash('success', 'Le propriétaire a été modifié avec succès.');

                return $this->redirectToRoute('app_owner_show', ['id' => $owner->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la modification du propriétaire : ' . $e->getMessage());
            }
        }

        return $this->render('owner/edit.html.twig', [
            'owner' => $owner,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_owner_delete', methods: ['POST'])]
    public function delete(Request $request, Owner $owner, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        // Vérifier l'accès au propriétaire selon l'organisation/société
        if ($user && !$this->canAccessOwner($user, $owner)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce propriétaire.');
        }

        if ($this->isCsrfTokenValid('delete'.$owner->getId(), $request->getPayload()->getString('_token'))) {
            // Vérifier si le propriétaire a des biens
            if ($owner->getProperties()->count() > 0) {
                $this->addFlash('error', 'Impossible de supprimer ce propriétaire car il possède des biens immobiliers. Veuillez d\'abord supprimer ou réassigner ses biens.');
                return $this->redirectToRoute('app_owner_show', ['id' => $owner->getId()]);
            }

            try {
                $entityManager->remove($owner);
                $entityManager->flush();

                $this->addFlash('success', 'Le propriétaire a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression du propriétaire : ' . $e->getMessage());
                return $this->redirectToRoute('app_owner_show', ['id' => $owner->getId()]);
            }
        }

        return $this->redirectToRoute('app_owner_index');
    }

    #[Route('/{id}/proprietes', name: 'app_owner_properties', methods: ['GET'])]
    public function properties(Owner $owner): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        // Vérifier l'accès au propriétaire selon l'organisation/société
        if ($user && !$this->canAccessOwner($user, $owner)) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce propriétaire.');
        }

        return $this->render('owner/properties.html.twig', [
            'owner' => $owner,
            'properties' => $owner->getProperties(),
        ]);
    }

    #[Route('/statistiques', name: 'app_owner_statistics', methods: ['GET'])]
    public function statistics(OwnerRepository $ownerRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        
        // Filtrer par organisation et société de l'utilisateur
        $organization = $user ? $user->getOrganization() : null;
        $company = $user ? $user->getCompany() : null;

        $owners = $ownerRepository->findAllFiltered($organization, $company);
        
        // Calculer les statistiques à partir des propriétaires filtrés
        $stats = [
            'total' => count($owners),
            'particuliers' => count(array_filter($owners, fn($o) => $o->getOwnerType() === 'Particulier')),
            'societes' => count(array_filter($owners, fn($o) => $o->getOwnerType() !== 'Particulier')),
        ];
        
        // Filtrer les propriétaires avec propriétés actives
        $allOwnersWithProperties = $ownerRepository->findWithActiveProperties();
        $ownersWithProperties = [];
        foreach ($allOwnersWithProperties as $owner) {
            if ($company && $owner->getCompany() === $company) {
                $ownersWithProperties[] = $owner;
            } elseif ($organization && !$company && $owner->getOrganization() === $organization) {
                $ownersWithProperties[] = $owner;
            } elseif (!$organization && !$company) {
                $ownersWithProperties[] = $owner;
            }
        }

        return $this->render('owner/statistics.html.twig', [
            'stats' => $stats,
            'owners_with_properties' => $ownersWithProperties,
        ]);
    }

    /**
     * Vérifie si un utilisateur peut accéder à un propriétaire
     */
    private function canAccessOwner($user, Owner $owner): bool
    {
        $userCompany = $user->getCompany();
        $userOrganization = $user->getOrganization();

        // Super admin sans organisation/société peut tout voir
        if (!$userOrganization && !$userCompany) {
            return true;
        }

        // Vérifier par société en priorité
        if ($userCompany && $owner->getCompany() === $userCompany) {
            return true;
        }

        // Vérifier par organisation
        if ($userOrganization && !$userCompany && $owner->getOrganization() === $userOrganization) {
            return true;
        }

        return false;
    }
}

