<?php

namespace App\Message;

/**
 * Message pour la création des configurations comptables
 */
class CreateAccountingConfigurationsMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
