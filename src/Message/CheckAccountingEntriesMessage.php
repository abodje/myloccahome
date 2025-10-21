<?php

namespace App\Message;

/**
 * Message pour la vérification des écritures comptables
 */
class CheckAccountingEntriesMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
