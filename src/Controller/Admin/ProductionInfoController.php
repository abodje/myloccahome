<?php

namespace App\Controller\Admin;

use App\Service\ProductionConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/production', name: 'app_admin_production_')]
#[IsGranted('ROLE_ADMIN')]
class ProductionInfoController extends AbstractController
{
    #[Route('/info', name: 'info', methods: ['GET'])]
    public function info(ProductionConfigService $productionConfig): Response
    {
        $config = $productionConfig->getProductionConfig();
        $environmentConfig = $productionConfig->getEnvironmentDomainConfig();
        $debugInfo = $productionConfig->getProductionDebugInfo();

        return $this->render('admin/production_info.html.twig', [
            'config' => $config,
            'environment_config' => $environmentConfig,
            'debug_info' => $debugInfo,
            'is_production' => $productionConfig->isProduction(),
        ]);
    }
}
