<?php

namespace App\Message;

/**
 * Message pour le test de génération de loyers avec configuration
 */
class TestRentGenerationWithConfigMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
