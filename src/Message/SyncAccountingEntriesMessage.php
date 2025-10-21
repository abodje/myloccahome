<?php

namespace App\Message;

/**
 * Message pour la synchronisation des écritures comptables
 */
class SyncAccountingEntriesMessage
{
    public function __construct(
        public readonly ?int $organizationId = null,
        public readonly ?int $companyId = null
    ) {
    }
}
