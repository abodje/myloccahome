<?php

namespace App\Message;

/**
 * Message pour le test de configuration comptable
 */
class TestAccountingConfigMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
