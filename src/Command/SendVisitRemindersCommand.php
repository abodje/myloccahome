<?php

namespace App\Command;

use App\Repository\VisitRepository;
use App\Service\NotificationService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-visit-reminders',
    description: 'Envoie des rappels email et SMS pour les visites prévues demain (J-1)'
)]
class SendVisitRemindersCommand extends Command
{
    public function __construct(
        private VisitRepository $visitRepository,
        private NotificationService $notificationService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Envoi des rappels de visite (J-1)');

        // Calculer la plage horaire de demain
        $tomorrow = new \DateTime('+1 day');
        $startOfDay = (clone $tomorrow)->setTime(0, 0, 0);
        $endOfDay = (clone $tomorrow)->setTime(23, 59, 59);

        $io->info(sprintf(
            'Recherche des visites prévues le %s entre %s et %s',
            $tomorrow->format('d/m/Y'),
            $startOfDay->format('H:i'),
            $endOfDay->format('H:i')
        ));

        // Récupérer les visites de demain avec status 'confirmed'
        $visits = $this->visitRepository->createQueryBuilder('v')
            ->join('v.visitSlot', 'vs')
            ->where('vs.startTime BETWEEN :start AND :end')
            ->andWhere('v.status = :status')
            ->setParameter('start', $startOfDay)
            ->setParameter('end', $endOfDay)
            ->setParameter('status', 'confirmed')
            ->getQuery()
            ->getResult();

        if (empty($visits)) {
            $io->warning('Aucune visite confirmée trouvée pour demain.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Trouvé %d visite(s) confirmée(s) pour demain', count($visits)));

        $emailSent = 0;
        $emailFailed = 0;
        $smsSent = 0;
        $smsFailed = 0;

        $io->progressStart(count($visits));

        foreach ($visits as $visit) {
            try {
                $slot = $visit->getVisitSlot();
                $property = $slot->getProperty();

                $io->text(sprintf(
                    'Traitement visite #%d - %s %s - %s à %s',
                    $visit->getId(),
                    $visit->getFirstName(),
                    $visit->getLastName(),
                    $property->getFullAddress(),
                    $slot->getStartTime()->format('H:i')
                ));

                // Envoyer email de rappel
                try {
                    $emailResult = $this->notificationService->sendVisitReminderEmail($visit);
                    if ($emailResult) {
                        $emailSent++;
                        $io->text('  ✅ Email envoyé à ' . $visit->getEmail());
                    } else {
                        $emailFailed++;
                        $io->text('  ❌ Échec email pour ' . $visit->getEmail());
                    }
                } catch (\Exception $e) {
                    $emailFailed++;
                    $io->text('  ❌ Erreur email: ' . $e->getMessage());
                    $this->logger->error('Erreur envoi email rappel visite', [
                        'visit_id' => $visit->getId(),
                        'error' => $e->getMessage()
                    ]);
                }

                // Envoyer SMS de rappel
                try {
                    $smsResult = $this->notificationService->sendVisitReminderSms($visit);
                    if ($smsResult) {
                        $smsSent++;
                        $io->text('  ✅ SMS envoyé à ' . $visit->getPhone());
                    } else {
                        $smsFailed++;
                        $io->text('  ⚠️  SMS non envoyé (service désactivé ou erreur)');
                    }
                } catch (\Exception $e) {
                    $smsFailed++;
                    $io->text('  ❌ Erreur SMS: ' . $e->getMessage());
                    $this->logger->error('Erreur envoi SMS rappel visite', [
                        'visit_id' => $visit->getId(),
                        'error' => $e->getMessage()
                    ]);
                }

                $io->progressAdvance();

            } catch (\Exception $e) {
                $emailFailed++;
                $smsFailed++;
                $io->error(sprintf('Erreur générale pour visite #%d: %s', $visit->getId(), $e->getMessage()));
                $this->logger->error('Erreur traitement rappel visite', [
                    'visit_id' => $visit->getId(),
                    'error' => $e->getMessage()
                ]);
                $io->progressAdvance();
            }
        }

        $io->progressFinish();

        // Afficher le résumé
        $io->newLine();
        $io->section('📊 Résumé de l\'envoi');

        $io->table(
            ['Type', 'Envoyés', 'Échecs', 'Total'],
            [
                ['📧 Email', $emailSent, $emailFailed, $emailSent + $emailFailed],
                ['📱 SMS', $smsSent, $smsFailed, $smsSent + $smsFailed],
            ]
        );

        $this->logger->info('Commande send-visit-reminders terminée', [
            'visits_found' => count($visits),
            'emails_sent' => $emailSent,
            'emails_failed' => $emailFailed,
            'sms_sent' => $smsSent,
            'sms_failed' => $smsFailed
        ]);

        if ($emailFailed > 0 || $smsFailed > 0) {
            $io->warning(sprintf(
                'Terminé avec des erreurs: %d email(s) et %d SMS échoués',
                $emailFailed,
                $smsFailed
            ));
            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Tous les rappels ont été envoyés avec succès ! (%d emails, %d SMS)',
            $emailSent,
            $smsSent
        ));

        return Command::SUCCESS;
    }
}
