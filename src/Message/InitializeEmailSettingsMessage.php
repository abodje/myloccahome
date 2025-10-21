<?php

namespace App\Message;

/**
 * Message pour l'initialisation des paramètres email
 */
class InitializeEmailSettingsMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
