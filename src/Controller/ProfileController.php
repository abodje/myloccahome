<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserProfileType;
use App\Form\UserPasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/mon-profil')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/identifiants', name: 'app_profile_credentials', methods: ['GET', 'POST'])]
    public function credentials(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $newEmail = $request->request->get('email');
            if ($newEmail && $newEmail !== $user->getEmail()) {
                $user->setEmail($newEmail);
                $entityManager->flush();
                $this->addFlash('success', 'Adresse e-mail mise à jour avec succès.');
                return $this->redirectToRoute('app_profile_credentials');
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'active_tab' => 'credentials',
        ]);
    }

    #[Route('/informations', name: 'app_profile_information', methods: ['GET', 'POST'])]
    public function information(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // Traitement du formulaire d'informations personnelles
            $user->setFirstName($request->request->get('firstName', $user->getFirstName()));
            $user->setLastName($request->request->get('lastName', $user->getLastName()));
            $user->setMobilePhone($request->request->get('mobilePhone', $user->getMobilePhone()));
            $user->setAddress($request->request->get('address', $user->getAddress()));
            $user->setCity($request->request->get('city', $user->getCity()));
            $user->setPostalCode($request->request->get('postalCode', $user->getPostalCode()));

            if ($request->request->get('birthDate')) {
                $user->setBirthDate(new \DateTime($request->request->get('birthDate')));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Informations personnelles mises à jour avec succès.');
            return $this->redirectToRoute('app_profile_information');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'active_tab' => 'information',
        ]);
    }

    #[Route('/mode-paiement', name: 'app_profile_payment_method', methods: ['GET', 'POST'])]
    public function paymentMethod(Request $request): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // Note: User n'a pas de champ preferredPaymentMethod
            // Cette fonctionnalité devrait être gérée via Tenant ou une autre entité
            $this->addFlash('info', 'Cette fonctionnalité sera bientôt disponible.');
            return $this->redirectToRoute('app_profile_payment_method');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'active_tab' => 'payment',
        ]);
    }

    #[Route('/confidentialite', name: 'app_profile_privacy', methods: ['GET', 'POST'])]
    public function privacy(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // Récupérer les consentements depuis le formulaire
            $fonciaConsent = $request->request->has('foncia_communications');
            $partnerConsent = $request->request->has('partner_communications');

            // Mettre à jour les consentements
            $user->setConsent('foncia_communications', $fonciaConsent);
            $user->setConsent('partner_communications', $partnerConsent);

            // Sauvegarder en base de données
            $entityManager->flush();

            $this->addFlash('success', 'Vos préférences de confidentialité ont été enregistrées.');
            return $this->redirectToRoute('app_profile_privacy');
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'active_tab' => 'privacy',
        ]);
    }

    #[Route('/modifier-mot-de-passe', name: 'app_profile_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Validation basique
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            } elseif (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            } elseif (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            } else {
                // Hasher et enregistrer le nouveau mot de passe
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
                $entityManager->flush();

                $this->addFlash('success', 'Mot de passe modifié avec succès.');
                return $this->redirectToRoute('app_profile_credentials');
            }
        }

        return $this->render('profile/change_password.html.twig', [
            'user' => $user,
        ]);
    }
}
