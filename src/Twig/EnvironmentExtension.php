<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EnvironmentExtension extends AbstractExtension
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_environment_active', [$this, 'isEnvironmentActive']),
            new TwigFunction('get_current_environment', [$this, 'getCurrentEnvironment']),
            new TwigFunction('get_environment_url', [$this, 'getEnvironmentUrl']),
            new TwigFunction('get_environment_code', [$this, 'getEnvironmentCode']),
        ];
    }

    public function isEnvironmentActive(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return false;
        }

        return $request->attributes->has('_environment') ||
               $request->getSession()->has('current_environment');
    }

    public function getCurrentEnvironment(): ?array
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $environment = $request->attributes->get('_environment');

        if ($environment) {
            return [
                'id' => $environment->getId(),
                'name' => $environment->getName(),
                'code' => $environment->getSubdomain(),
                'type' => $environment->getType(),
                'url' => $environment->getUrl()
            ];
        }

        return null;
    }

    public function getEnvironmentUrl(string $envCode): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return '';
        }

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        $url = $scheme . '://' . $host;
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $url .= ':' . $port;
        }

        return $url . '/env/' . $envCode;
    }

    public function getEnvironmentCode(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        return $request->attributes->get('_environment_code') ??
               $request->getSession()->get('current_environment');
    }
}
