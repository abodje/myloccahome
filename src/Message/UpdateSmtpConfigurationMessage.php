<?php

namespace App\Message;

/**
 * Message pour la mise à jour de la configuration SMTP
 */
class UpdateSmtpConfigurationMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
