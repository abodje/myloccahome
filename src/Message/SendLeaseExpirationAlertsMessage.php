<?php

namespace App\Message;

/**
 * Message pour les alertes d'expiration de contrat
 */
class SendLeaseExpirationAlertsMessage
{
    public function __construct(
        public readonly ?int $organizationId = null,
        public readonly ?int $companyId = null
    ) {
    }
}
