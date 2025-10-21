<?php

namespace App\Message;

/**
 * Message pour la correction de la table comptable
 */
class FixAccountingTableMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
