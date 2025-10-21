<?php

namespace App\Message;

/**
 * Message pour la création d'un super administrateur
 */
class CreateSuperAdminMessage
{
    public function __construct(
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $password
    ) {
    }
}
