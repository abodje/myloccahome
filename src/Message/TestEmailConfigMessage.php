<?php

namespace App\Message;

/**
 * Message pour le test de configuration email
 */
class TestEmailConfigMessage
{
    public function __construct(
        public readonly string $testEmail
    ) {
    }
}
