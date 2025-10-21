<?php

namespace App\Message;

/**
 * Message pour les rappels de paiement
 */
class SendPaymentRemindersMessage
{
    public function __construct(
        public readonly ?int $organizationId = null,
        public readonly ?int $companyId = null
    ) {
    }
}
