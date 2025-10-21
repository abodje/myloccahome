<?php

namespace App\Message;

/**
 * Message pour la démonstration du système comptable
 */
class DemoAccountingSystemMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
