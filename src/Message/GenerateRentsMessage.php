<?php

namespace App\Message;

/**
 * Message pour la génération automatique des loyers
 */
class GenerateRentsMessage
{
    public function __construct(
        public readonly ?\DateTime $forMonth = null,
        public readonly ?int $organizationId = null,
        public readonly ?int $companyId = null
    ) {
    }
}
