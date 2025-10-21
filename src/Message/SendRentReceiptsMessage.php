<?php

namespace App\Message;

/**
 * Message pour l'envoi des quittances de loyer
 */
class SendRentReceiptsMessage
{
    public function __construct(
        public readonly ?\DateTime $forMonth = null,
        public readonly ?int $organizationId = null,
        public readonly ?int $companyId = null
    ) {
    }
}
