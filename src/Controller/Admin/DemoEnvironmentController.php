<?php

namespace App\Controller\Admin;

use App\Service\DemoEnvironmentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/demo')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class DemoEnvironmentController extends AbstractController
{
    public function __construct(
        private DemoEnvironmentService $demoEnvironmentService
    ) {
    }

    #[Route('/', name: 'app_admin_demo_index', methods: ['GET'])]
    public function index(): Response
    {
        $environments = $this->demoEnvironmentService->listDemoEnvironments();

        return $this->render('admin/demo/index.html.twig', [
            'environments' => $environments,
        ]);
    }

    #[Route('/create', name: 'app_admin_demo_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $email = $request->request->get('email');
        $firstName = $request->request->get('first_name');
        $lastName = $request->request->get('last_name');

        if (!$email || !$firstName || !$lastName) {
            $this->addFlash('error', 'Veuillez remplir tous les champs.');
            return $this->redirectToRoute('app_admin_demo_index');
        }

        // Créer un utilisateur temporaire pour la démo
        $user = new \App\Entity\User();
        $user->setEmail($email);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);

        $result = $this->demoEnvironmentService->createDemoEnvironment($user);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('app_admin_demo_index');
    }

    #[Route('/delete/{subdomain}', name: 'app_admin_demo_delete', methods: ['POST'])]
    public function delete(string $subdomain): Response
    {
        $success = $this->demoEnvironmentService->deleteDemoEnvironment($subdomain);

        if ($success) {
            $this->addFlash('success', "Environnement de démo '{$subdomain}' supprimé avec succès.");
        } else {
            $this->addFlash('error', "Erreur lors de la suppression de l'environnement '{$subdomain}'.");
        }

        return $this->redirectToRoute('app_admin_demo_index');
    }

    #[Route('/refresh/{subdomain}', name: 'app_admin_demo_refresh', methods: ['POST'])]
    public function refresh(string $subdomain): Response
    {
        // Supprimer et recréer l'environnement
        $this->demoEnvironmentService->deleteDemoEnvironment($subdomain);
        
        // Créer un nouvel utilisateur temporaire
        $user = new \App\Entity\User();
        $user->setEmail("demo-{$subdomain}@example.com");
        $user->setFirstName("Demo");
        $user->setLastName("User");

        $result = $this->demoEnvironmentService->createDemoEnvironment($user);

        if ($result['success']) {
            $this->addFlash('success', "Environnement de démo '{$subdomain}' rafraîchi avec succès.");
        } else {
            $this->addFlash('error', "Erreur lors du rafraîchissement de l'environnement '{$subdomain}'.");
        }

        return $this->redirectToRoute('app_admin_demo_index');
    }
}
