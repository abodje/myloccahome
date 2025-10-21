<?php

namespace App\Message;

/**
 * Message pour le nettoyage de l'audit log
 */
class AuditCleanupMessage
{
    public function __construct(
        public readonly int $daysToKeep = 90
    ) {
    }
}
