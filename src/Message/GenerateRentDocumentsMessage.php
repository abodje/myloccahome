<?php

namespace App\Message;

/**
 * Message pour la génération des quittances et avis d'échéances
 */
class GenerateRentDocumentsMessage
{
    public function __construct(
        public readonly ?\DateTime $forMonth = null,
        public readonly ?int $organizationId = null,
        public readonly ?int $companyId = null
    ) {
    }
}
