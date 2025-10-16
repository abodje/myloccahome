<?php

namespace App\Controller\Admin;

use App\Entity\EmailTemplate;
use App\Repository\EmailTemplateRepository;
use App\Service\EmailCustomizationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/templates-email')]
class EmailTemplateController extends AbstractController
{
    #[Route('/', name: 'app_admin_email_template_index', methods: ['GET'])]
    public function index(EmailTemplateRepository $repository): Response
    {
        $templates = $repository->findBy([], ['name' => 'ASC']);
        $stats = $repository->getStatistics();
        $mostUsed = $repository->findMostUsed();

        return $this->render('admin/email_template/index.html.twig', [
            'templates' => $templates,
            'stats' => $stats,
            'most_used' => $mostUsed,
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_email_template_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EmailCustomizationService $emailService): Response
    {
        $template = new EmailTemplate();

        if ($request->isMethod('POST')) {
            $template->setCode($request->request->get('code'))
                     ->setName($request->request->get('name'))
                     ->setSubject($request->request->get('subject'))
                     ->setHtmlContent($request->request->get('html_content'))
                     ->setTextContent($request->request->get('text_content'))
                     ->setDescription($request->request->get('description'))
                     ->setIsActive($request->request->has('is_active'));

            $entityManager->persist($template);
            $entityManager->flush();

            $this->addFlash('success', 'Template créé avec succès.');
            return $this->redirectToRoute('app_admin_email_template_index');
        }

        return $this->render('admin/email_template/new.html.twig', [
            'template' => $template,
            'variables' => $emailService->getAllAvailableVariables(),
        ]);
    }

    #[Route('/{id}', name: 'app_admin_email_template_show', methods: ['GET'])]
    public function show(EmailTemplate $template, EmailCustomizationService $emailService): Response
    {
        $preview = $emailService->previewTemplate($template);

        return $this->render('admin/email_template/show.html.twig', [
            'template' => $template,
            'preview' => $preview,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_email_template_edit', methods: ['GET', 'POST'])]
    public function edit(EmailTemplate $template, Request $request, EntityManagerInterface $entityManager, EmailCustomizationService $emailService): Response
    {
        if ($request->isMethod('POST')) {
            $template->setName($request->request->get('name'))
                     ->setSubject($request->request->get('subject'))
                     ->setHtmlContent($request->request->get('html_content'))
                     ->setTextContent($request->request->get('text_content'))
                     ->setDescription($request->request->get('description'))
                     ->setIsActive($request->request->has('is_active'));

            $entityManager->flush();

            $this->addFlash('success', 'Template modifié avec succès.');
            return $this->redirectToRoute('app_admin_email_template_show', ['id' => $template->getId()]);
        }

        return $this->render('admin/email_template/edit.html.twig', [
            'template' => $template,
            'variables' => $emailService->getAllAvailableVariables(),
        ]);
    }

    #[Route('/{id}/previsualiser', name: 'app_admin_email_template_preview', methods: ['POST'])]
    public function preview(Request $request, EmailCustomizationService $emailService): Response
    {
        $htmlContent = $request->request->get('html_content', '');

        // Créer un template temporaire pour la prévisualisation
        $tempTemplate = new EmailTemplate();
        $tempTemplate->setHtmlContent($htmlContent);

        $preview = $emailService->previewTemplate($tempTemplate);

        return new Response($preview);
    }

    #[Route('/{id}/toggle', name: 'app_admin_email_template_toggle', methods: ['POST'])]
    public function toggle(EmailTemplate $template, EntityManagerInterface $entityManager): Response
    {
        $template->setIsActive(!$template->isActive());
        $entityManager->flush();

        $status = $template->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Template {$status} avec succès.");

        return $this->redirectToRoute('app_admin_email_template_index');
    }

    #[Route('/{id}/dupliquer', name: 'app_admin_email_template_duplicate', methods: ['POST'])]
    public function duplicate(EmailTemplate $template, EntityManagerInterface $entityManager): Response
    {
        $newTemplate = new EmailTemplate();
        $newTemplate->setCode($template->getCode() . '_COPY_' . time())
                    ->setName($template->getName() . ' (Copie)')
                    ->setSubject($template->getSubject())
                    ->setHtmlContent($template->getHtmlContent())
                    ->setTextContent($template->getTextContent())
                    ->setDescription($template->getDescription())
                    ->setAvailableVariables($template->getAvailableVariables())
                    ->setIsActive(false)
                    ->setIsSystem(false);

        $entityManager->persist($newTemplate);
        $entityManager->flush();

        $this->addFlash('success', 'Template dupliqué avec succès.');
        return $this->redirectToRoute('app_admin_email_template_edit', ['id' => $newTemplate->getId()]);
    }

    #[Route('/initialiser', name: 'app_admin_email_template_initialize', methods: ['POST'])]
    public function initialize(EmailCustomizationService $emailService): Response
    {
        try {
            $emailService->initializeDefaultTemplates();
            $this->addFlash('success', 'Templates par défaut initialisés avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'initialisation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_email_template_index');
    }

    #[Route('/{id}/supprimer', name: 'app_admin_email_template_delete', methods: ['POST'])]
    public function delete(EmailTemplate $template, EntityManagerInterface $entityManager): Response
    {
        if ($template->isSystem()) {
            $this->addFlash('error', 'Les templates système ne peuvent pas être supprimés.');
            return $this->redirectToRoute('app_admin_email_template_index');
        }

        $entityManager->remove($template);
        $entityManager->flush();

        $this->addFlash('success', 'Template supprimé avec succès.');
        return $this->redirectToRoute('app_admin_email_template_index');
    }
}

