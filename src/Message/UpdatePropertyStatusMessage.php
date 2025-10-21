<?php

namespace App\Message;

/**
 * Message pour la mise à jour du statut des propriétés
 */
class UpdatePropertyStatusMessage
{
    public function __construct(
        public readonly ?int $organizationId = null,
        public readonly ?int $companyId = null
    ) {
    }
}
