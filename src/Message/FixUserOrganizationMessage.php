<?php

namespace App\Message;

/**
 * Message pour la correction des utilisateurs sans organisation
 */
class FixUserOrganizationMessage
{
    public function __construct(
        public readonly bool $autoFixTenants = true,
        public readonly bool $logDetails = true
    ) {
    }
}
