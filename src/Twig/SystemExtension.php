<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SystemExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('disk_free_space', [$this, 'getDiskFreeSpace']),
            new TwigFunction('disk_total_space', [$this, 'getDiskTotalSpace']),
            new TwigFunction('memory_get_usage', [$this, 'getMemoryUsage']),
            new TwigFunction('memory_get_peak_usage', [$this, 'getMemoryPeakUsage']),
            new TwigFunction('php_version', [$this, 'getPhpVersion']),
            new TwigFunction('ini_get', [$this, 'getIniValue']),
        ];
    }

    /**
     * Retourne l'espace disque libre en octets
     */
    public function getDiskFreeSpace(string $directory = '.'): float
    {
        try {
            return (float) disk_free_space($directory);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Retourne l'espace disque total en octets
     */
    public function getDiskTotalSpace(string $directory = '.'): float
    {
        try {
            return (float) disk_total_space($directory);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Retourne l'utilisation mémoire actuelle
     */
    public function getMemoryUsage(bool $realUsage = false): int
    {
        return memory_get_usage($realUsage);
    }

    /**
     * Retourne le pic d'utilisation mémoire
     */
    public function getMemoryPeakUsage(bool $realUsage = false): int
    {
        return memory_get_peak_usage($realUsage);
    }

    /**
     * Retourne la version de PHP
     */
    public function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * Retourne une valeur de configuration PHP
     */
    public function getIniValue(string $option): string
    {
        return ini_get($option) ?: '';
    }

    /**
     * Formate une taille en octets en format lisible
     */
    public function formatBytes(float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
