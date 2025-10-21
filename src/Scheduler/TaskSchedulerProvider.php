<?php

namespace App\Scheduler;

use App\Message\GenerateRentsMessage;
use App\Message\GenerateRentDocumentsMessage;
use App\Message\SendRentReceiptsMessage;
use App\Message\SendPaymentRemindersMessage;
use App\Message\SendLeaseExpirationAlertsMessage;
use App\Message\SyncAccountingEntriesMessage;
use App\Message\UpdatePropertyStatusMessage;
use App\Message\CleanupDemoEnvironmentsMessage;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

class TaskSchedulerProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            // Génération automatique des loyers - Tous les jours à 9h00
            ->add(
                RecurringMessage::cron('0 9 * * *', new GenerateRentsMessage()),
                'generate_rents_daily'
            )
            // Génération des quittances et avis d'échéance - Le 1er de chaque mois à 8h00
            ->add(
                RecurringMessage::cron('0 8 1 * *', new GenerateRentDocumentsMessage()),
                'generate_rent_documents_monthly'
            )
            // Envoi des quittances - Tous les jours à 10h00
            ->add(
                RecurringMessage::cron('0 10 * * *', new SendRentReceiptsMessage()),
                'send_rent_receipts_daily'
            )
            // Rappels de paiement - Tous les jours à 14h00
            ->add(
                RecurringMessage::cron('0 14 * * *', new SendPaymentRemindersMessage()),
                'send_payment_reminders_daily'
            )
            // Alertes d'expiration de contrat - Tous les jours à 16h00
            ->add(
                RecurringMessage::cron('0 16 * * *', new SendLeaseExpirationAlertsMessage()),
                'send_lease_expiration_alerts_daily'
            )
            // Synchronisation des écritures comptables - Tous les jours à 18h00
            ->add(
                RecurringMessage::cron('0 18 * * *', new SyncAccountingEntriesMessage()),
                'sync_accounting_entries_daily'
            )
            // Mise à jour du statut des propriétés - Tous les jours à 20h00
            ->add(
                RecurringMessage::cron('0 20 * * *', new UpdatePropertyStatusMessage()),
                'update_property_status_daily'
            )
            // Nettoyage des environnements de démo - Tous les dimanches à 2h00
            ->add(
                RecurringMessage::cron('0 2 * * 0', new CleanupDemoEnvironmentsMessage()),
                'cleanup_demo_environments_weekly'
            );
    }
}
