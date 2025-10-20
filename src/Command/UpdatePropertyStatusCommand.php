<?php

namespace App\Command;

use App\Entity\Property;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-property-status',
    description: 'Met à jour le statut de toutes les propriétés selon leur occupation',
)]
class UpdatePropertyStatusCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour du statut des propriétés');

        // Récupérer toutes les propriétés
        $properties = $this->entityManager->getRepository(Property::class)->findAll();

        $io->writeln(sprintf('Traitement de %d propriétés...', count($properties)));

        $updatedCount = 0;
        $occupiedCount = 0;
        $freeCount = 0;

        foreach ($properties as $property) {
            $oldStatus = $property->getStatus();
            $hasActiveLease = $this->hasActiveLease($property);

            if ($hasActiveLease) {
                $newStatus = 'Occupé';
                $occupiedCount++;
            } else {
                $newStatus = 'Libre';
                $freeCount++;
            }

            // Mettre à jour le statut si nécessaire
            if ($oldStatus !== $newStatus) {
                $property->setStatus($newStatus);
                $this->entityManager->persist($property);
                $updatedCount++;

                $io->writeln(sprintf(
                    'Propriété #%d (%s): %s → %s',
                    $property->getId(),
                    $property->getFullAddress(),
                    $oldStatus,
                    $newStatus
                ));
            }
        }

        // Sauvegarder tous les changements
        $this->entityManager->flush();

        $io->success(sprintf(
            'Mise à jour terminée ! %d propriétés mises à jour, %d occupées, %d libres.',
            $updatedCount,
            $occupiedCount,
            $freeCount
        ));

        return Command::SUCCESS;
    }

    /**
     * Vérifie si une propriété a un bail actif
     */
    private function hasActiveLease(Property $property): bool
    {
        $now = new \DateTime();

        foreach ($property->getLeases() as $lease) {
            // Un bail est actif s'il a commencé et n'a pas encore fini
            if ($lease->getStartDate() <= $now &&
                ($lease->getEndDate() === null || $lease->getEndDate() >= $now) &&
                $lease->getStatus() === 'Actif') {
                return true;
            }
        }

        return false;
    }
}
