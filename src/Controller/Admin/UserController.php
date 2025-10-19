<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Organization;
use App\Entity\Company;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\OrganizationRepository;
use App\Repository\CompanyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/utilisateurs')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private CompanyRepository $companyRepository
    ) {
    }

    #[Route('/', name: 'app_admin_user_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Filtrer selon l'organisation/société de l'utilisateur connecté
        $qb = $this->userRepository->createQueryBuilder('u');

        if ($currentUser->getCompany()) {
            // Admin avec société spécifique : voir uniquement les utilisateurs de sa société
            $qb->where('u.company = :company')
               ->setParameter('company', $currentUser->getCompany());
        } elseif ($currentUser->getOrganization()) {
            // Admin sans société : voir tous les utilisateurs de son organisation
            $qb->where('u.organization = :organization')
               ->setParameter('organization', $currentUser->getOrganization());
        }
        // Super admin sans organisation/société : voir tous les utilisateurs (pas de filtre)

        $users = $qb->orderBy('u.createdAt', 'DESC')->getQuery()->getResult();

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $user = new User();

        // Pré-remplir l'organisation et la société selon l'utilisateur connecté
        if ($currentUser->getOrganization()) {
            $user->setOrganization($currentUser->getOrganization());
        }
        if ($currentUser->getCompany()) {
            $user->setCompany($currentUser->getCompany());
        }

        // Récupérer les organisations et sociétés disponibles
        $organizations = $this->organizationRepository->findAll();
        $companies = $this->companyRepository->findAll();

        // Si l'utilisateur a une société spécifique, filtrer les sociétés
        if ($currentUser->getCompany()) {
            $companies = [$currentUser->getCompany()];
        } elseif ($currentUser->getOrganization()) {
            // Filtrer les sociétés par organisation
            $companies = $this->companyRepository->findBy(['organization' => $currentUser->getOrganization()]);
        }

        $form = $this->createForm(UserType::class, $user, [
            'organizations' => $organizations,
            'companies' => $companies,
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hacher le mot de passe (récupérer depuis le formulaire)
            $plainPassword = $form->get('password')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Récupérer les organisations et sociétés disponibles
        $organizations = $this->organizationRepository->findAll();
        $companies = $this->companyRepository->findAll();

        // Si l'utilisateur a une société spécifique, filtrer les sociétés
        if ($currentUser->getCompany()) {
            $companies = [$currentUser->getCompany()];
        } elseif ($currentUser->getOrganization()) {
            // Filtrer les sociétés par organisation
            $companies = $this->companyRepository->findBy(['organization' => $currentUser->getOrganization()]);
        }

        $form = $this->createForm(UserType::class, $user, [
            'organizations' => $organizations,
            'companies' => $companies,
            'current_user' => $currentUser,
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Si un nouveau mot de passe a été fourni, le hacher
            if ($form->get('password')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été modifié avec succès.');
            return $this->redirectToRoute('app_admin_user_index');
        }

        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_user_index');
    }
}
