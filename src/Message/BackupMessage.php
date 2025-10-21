<?php

namespace App\Message;

/**
 * Message pour la sauvegarde automatique
 */
class BackupMessage
{
    public function __construct(
        public readonly bool $cleanOld = true,
        public readonly int $keepDays = 30
    ) {
    }
}
