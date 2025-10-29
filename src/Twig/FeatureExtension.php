<?php

namespace App\Twig;

use App\Service\FeatureAccessService;
use App\Service\QuotaUpgradeService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FeatureExtension extends AbstractExtension
{
    public function __construct(
        private FeatureAccessService $featureAccessService,
        private QuotaUpgradeService $quotaUpgradeService,
        private Security $security
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('has_feature', [$this, 'hasFeature']),
            new TwigFunction('feature_label', [$this, 'getFeatureLabel']),
            new TwigFunction('feature_icon', [$this, 'getFeatureIcon']),
            new TwigFunction('feature_block_message', [$this, 'getFeatureBlockMessage']),
            new TwigFunction('required_plan', [$this, 'getRequiredPlan']),
            new TwigFunction('quota_upgrade_recommendation', [$this, 'getQuotaUpgradeRecommendation']),
        ];
    }

    public function hasFeature(string $feature): bool
    {
        $user = $this->security->getUser();
        return $this->featureAccessService->userHasAccess($user, $feature);
    }

    public function getFeatureLabel(string $feature): string
    {
        return $this->featureAccessService->getFeatureLabel($feature);
    }

    public function getFeatureIcon(string $feature): string
    {
        return $this->featureAccessService->getFeatureIcon($feature);
    }

    public function getFeatureBlockMessage(string $feature): string
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->security->getUser();
        if (!$user || !method_exists($user, 'getOrganization') || !$user->getOrganization()) {
            return "Vous devez être connecté pour accéder à cette fonctionnalité.";
        }

        return $this->featureAccessService->getFeatureBlockMessage($feature, $user->getOrganization());
    }

    public function getRequiredPlan(string $feature): string
    {
        return $this->featureAccessService->getRequiredPlan($feature);
    }

    /**
     * Retourne la recommandation d'upgrade si un quota est atteint
     */
    public function getQuotaUpgradeRecommendation(): ?array
    {
        $user = $this->security->getUser();
        
        if (!$user || !method_exists($user, 'getOrganization') || !$user->getOrganization()) {
            return null;
        }

        return $this->quotaUpgradeService->getUpgradeRecommendation($user->getOrganization());
    }
}

