<?php

namespace App\Message;

/**
 * Message pour la configuration du système comptable
 */
class SetupAccountingSystemMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
