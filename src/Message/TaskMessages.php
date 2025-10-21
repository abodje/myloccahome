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

/**
 * Message pour le nettoyage des environnements de démo
 */
class CleanupDemoEnvironmentsMessage
{
    public function __construct()
    {
    }
}

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

/**
 * Message pour le nettoyage de l'audit log
 */
class AuditCleanupMessage
{
    public function __construct(
        public readonly int $daysToKeep = 90
    ) {
    }
}

/**
 * Message pour la sauvegarde automatique
 */
class BackupMessage
{
    public function __construct(
        public readonly bool $cleanOld = true,
        public readonly int $keepDays = 30
    ) {
    }
}

/**
 * Message pour le test de configuration email
 */
class TestEmailConfigMessage
{
    public function __construct(
        public readonly string $testEmail
    ) {
    }
}

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

/**
 * Message pour la création d'environnements de démo
 */
class DemoCreateMessage
{
    public function __construct(
        public readonly int $defaultDays = 14,
        public readonly bool $autoCleanup = true,
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour la création des configurations comptables
 */
class CreateAccountingConfigurationsMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour le test de configuration comptable
 */
class TestAccountingConfigMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour la vérification des écritures comptables
 */
class CheckAccountingEntriesMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour le test de génération de loyers avec configuration
 */
class TestRentGenerationWithConfigMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour la démonstration du système comptable
 */
class DemoAccountingSystemMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour la correction de la table comptable
 */
class FixAccountingTableMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour la configuration du système comptable
 */
class SetupAccountingSystemMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour l'initialisation des paramètres email
 */
class InitializeEmailSettingsMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}

/**
 * Message pour le test des paramètres email
 */
class TestEmailSettingsMessage
{
    public function __construct(
        public readonly string $testEmail = 'info@app.lokapro.tech',
        public readonly bool $logDetails = true
    ) {
    }
}

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

/**
 * Message pour la mise à jour de la configuration SMTP
 */
class UpdateSmtpConfigurationMessage
{
    public function __construct(
        public readonly bool $logDetails = true
    ) {
    }
}
