<?php

namespace App\Message;

/**
 * Message pour la création d'environnements de démo
 */
class DemoCreateMessage
{
    public function __construct(
        public readonly int $defaultDays = 14,
        public readonly bool $autoCleanup = true,
        public readonly bool $logDetails = true
    ) {
    }
}
