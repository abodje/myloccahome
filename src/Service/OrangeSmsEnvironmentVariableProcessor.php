<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Processeur d'environnement personnalisé pour générer dynamiquement
 * la DSN Orange SMS à partir des paramètres de configuration
 */
class OrangeSmsEnvironmentVariableProcessor implements EnvVarProcessorInterface
{
    public function __construct(
        private OrangeSmsDsnService $orangeSmsDsnService
    ) {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): mixed
    {
        if ($prefix === 'orange_sms_dsn') {
            return $this->orangeSmsDsnService->generateDsn();
        }

        throw new \InvalidArgumentException(sprintf('Unsupported env var prefix "%s".', $prefix));
    }

    public static function getProvidedTypes(): array
    {
        return [
            'orange_sms_dsn' => 'string',
        ];
    }
}
