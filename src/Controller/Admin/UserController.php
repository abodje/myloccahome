<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/utilisateurs')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_admin_users', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $role = $request->query->get('role');

        if ($role) {
            $users = $userRepository->findByRole($role);
        } else {
            $users = $userRepository->findBy([], ['createdAt' => 'DESC']);
        }

        $stats = [
            'total' => $userRepository->count([]),
            'active' => $userRepository->count(['isActive' => true]),
            'admins' => count($userRepository->findAdmins()),
            'managers' => count($userRepository->findManagers()),
            'tenants' => count($userRepository->findTenants()),
        ];

        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'current_role' => $role,
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();

        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'))
                 ->setFirstName($request->request->get('firstName'))
                 ->setLastName($request->request->get('lastName'))
                 ->setPhone($request->request->get('phone'))
                 ->setAddress($request->request->get('address'))
                 ->setCity($request->request->get('city'))
                 ->setPostalCode($request->request->get('postalCode'))
                 ->setIsActive($request->request->has('isActive'));

            // Gestion des rôles
            $roles = $request->request->all('roles') ?? [];
            if (empty($roles)) {
                $roles = ['ROLE_USER'];
            }
            $user->setRoles($roles);

            // Hash du mot de passe
            $password = $request->request->get('password');
            if ($password) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_new.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('admin/user_show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(User $user, Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'))
                 ->setFirstName($request->request->get('firstName'))
                 ->setLastName($request->request->get('lastName'))
                 ->setPhone($request->request->get('phone'))
                 ->setAddress($request->request->get('address'))
                 ->setCity($request->request->get('city'))
                 ->setPostalCode($request->request->get('postalCode'))
                 ->setIsActive($request->request->has('isActive'));

            // Gestion des rôles
            $roles = $request->request->all('roles') ?? [];
            if (empty($roles)) {
                $roles = ['ROLE_USER'];
            }
            $user->setRoles($roles);

            // Changement de mot de passe (optionnel)
            $password = $request->request->get('password');
            if ($password) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur modifié avec succès.');
            return $this->redirectToRoute('app_admin_user_show', ['id' => $user->getId()]);
        }

        return $this->render('admin/user_edit.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/toggle', name: 'app_admin_user_toggle', methods: ['POST'])]
    public function toggle(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setActive(!$user->isActive());
        $entityManager->flush();

        $status = $user->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Utilisateur {$status} avec succès.");

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/{id}/supprimer', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(User $user, EntityManagerInterface $entityManager): Response
    {
        // Ne pas permettre de supprimer son propre compte
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_users');
        }

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        return $this->redirectToRoute('app_admin_users');
    }
}

