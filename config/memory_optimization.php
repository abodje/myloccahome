<?php

/**
 * Configuration d'optimisation de la mémoire
 * Ce fichier peut être inclus dans les scripts qui nécessitent plus de mémoire
 */

// Augmenter la limite de mémoire si nécessaire
$currentMemoryLimit = ini_get('memory_limit');
$currentMemoryLimitBytes = convertToBytes($currentMemoryLimit);

// Si la limite actuelle est inférieure à 1GB, l'augmenter
if ($currentMemoryLimitBytes < 1073741824) { // 1GB
    ini_set('memory_limit', '1024M');
    error_log("Limite de mémoire augmentée à 1024M");
}

// Configuration du garbage collection
gc_enable();

// Fonction utilitaire pour convertir les limites de mémoire
function convertToBytes(string $memoryLimit): int
{
    $memoryLimit = trim($memoryLimit);
    $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
    $value = (int) $memoryLimit;

    switch ($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }

    return $value;
}

// Fonction pour surveiller l'utilisation mémoire
function logMemoryUsage(string $context = ''): void
{
    $memoryUsage = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);
    $memoryLimit = ini_get('memory_limit');

    error_log(sprintf(
        "Utilisation mémoire %s: %s (pic: %s, limite: %s)",
        $context,
        formatBytes($memoryUsage),
        formatBytes($memoryPeak),
        $memoryLimit
    ));
}

// Fonction pour formater les bytes en format lisible
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

// Configuration pour les tâches longues
ini_set('max_execution_time', 0); // Pas de limite de temps
ini_set('default_socket_timeout', 300); // Timeout de 5 minutes pour les sockets

// Log de l'utilisation mémoire au début
logMemoryUsage('début du script');
