<?php

namespace App\Message;

/**
 * Message pour le test de la configuration SMTP
 */
class TestSmtpConfigurationMessage
{
    public function __construct(
        public readonly string $testEmail = 'info@app.lokapro.tech',
        public readonly bool $logDetails = true
    ) {
    }
}
