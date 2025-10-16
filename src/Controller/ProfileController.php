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
            }
        }

        return $this->render('profile/credentials.html.twig', [
            'user' => $user,
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
            $user->setPhone($request->request->get('phone', $user->getPhone()));
            $user->setAddress($request->request->get('address', $user->getAddress()));
            $user->setCity($request->request->get('city', $user->getCity()));
            $user->setPostalCode($request->request->get('postalCode', $user->getPostalCode()));
            $user->setCountry($request->request->get('country', $user->getCountry()));
            $user->setMaritalStatus($request->request->get('maritalStatus', $user->getMaritalStatus()));

            if ($request->request->get('birthDate')) {
                $user->setBirthDate(new \DateTime($request->request->get('birthDate')));
            }

            $entityManager->flush();
            $this->addFlash('success', 'Informations personnelles mises à jour avec succès.');
        }

        return $this->render('profile/information.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/mode-paiement', name: 'app_profile_payment_method', methods: ['GET', 'POST'])]
    public function paymentMethod(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $preferredMethod = $request->request->get('preferredPaymentMethod');
            if ($preferredMethod) {
                $user->setPreferredPaymentMethod($preferredMethod);
                $entityManager->flush();
                $this->addFlash('success', 'Mode de paiement préféré mis à jour avec succès.');
            }
        }

        return $this->render('profile/payment_method.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/confidentialite', name: 'app_profile_privacy', methods: ['GET', 'POST'])]
    public function privacy(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            // Vérifier le token CSRF
            $submittedToken = $request->request->get('token');
            if (!$this->isCsrfTokenValid('profile_privacy', $submittedToken)) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('app_profile_privacy');
            }

            // Gérer les consentements
            $consents = [];

            // Communications Foncia
            if ($request->request->has('foncia_communications')) {
                $consents['foncia_communications'] = true;
            } else {
                $consents['foncia_communications'] = false;
            }

            // Communications Partenaires
            if ($request->request->has('partner_communications')) {
                $consents['partner_communications'] = true;
            } else {
                $consents['partner_communications'] = false;
            }

            // Cookies d'analyse
            if ($request->request->has('analytics_cookies')) {
                $consents['analytics_cookies'] = true;
            } else {
                $consents['analytics_cookies'] = false;
            }

            // Cookies marketing
            if ($request->request->has('marketing_cookies')) {
                $consents['marketing_cookies'] = true;
            } else {
                $consents['marketing_cookies'] = false;
            }

            // Authentification à deux facteurs
            if ($request->request->has('two_factor_auth')) {
                $consents['two_factor_auth'] = true;
            } else {
                $consents['two_factor_auth'] = false;
            }

            // Notifications de connexion
            if ($request->request->has('login_notifications')) {
                $consents['login_notifications'] = true;
            } else {
                $consents['login_notifications'] = false;
            }

            // Sauvegarder les consentements
            $user->setConsents($consents);
            $entityManager->flush();

            $this->addFlash('success', 'Vos paramètres de confidentialité ont été mis à jour avec succès.');
            return $this->redirectToRoute('app_profile_privacy');
        }

        return $this->render('profile/privacy.html.twig', [
            'user' => $user,
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
