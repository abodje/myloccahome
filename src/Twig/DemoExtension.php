<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DemoExtension extends AbstractExtension
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_demo_environment', [$this, 'isDemoEnvironment']),
            new TwigFunction('get_demo_code', [$this, 'getDemoCode']),
            new TwigFunction('get_demo_url', [$this, 'getDemoUrl']),
        ];
    }

    public function isDemoEnvironment(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return false;
        }

        return $request->attributes->get('_demo_environment', false) ||
               $request->getSession()->has('demo_code');
    }

    public function getDemoCode(): ?string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        return $request->attributes->get('_demo_code') ??
               $request->getSession()->get('demo_code');
    }

    public function getDemoUrl(): ?string
    {
        $demoCode = $this->getDemoCode();

        if (!$demoCode) {
            return null;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return null;
        }

        $scheme = $request->getScheme();
        $host = $request->getHost();
        $port = $request->getPort();

        $url = $scheme . '://' . $host;
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $url .= ':' . $port;
        }

        return $url . '/demo/' . $demoCode;
    }
}
