<?php

namespace App\Message;

/**
 * Message pour le test des paramètres email
 */
class TestEmailSettingsMessage
{
    public function __construct(
        public readonly string $testEmail = 'info@app.lokapro.tech',
        public readonly bool $logDetails = true
    ) {
    }
}
