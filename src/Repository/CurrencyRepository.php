<?php

namespace App\Repository;

use App\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Currency>
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    /**
     * Trouve la devise par défaut
     */
    public function findDefault(): ?Currency
    {
        return $this->findOneBy(['isDefault' => true, 'isActive' => true]);
    }

    /**
     * Trouve toutes les devises actives
     */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['code' => 'ASC']);
    }

    /**
     * Trouve une devise par son code
     */
    public function findByCode(string $code): ?Currency
    {
        return $this->findOneBy(['code' => strtoupper($code)]);
    }

    /**
     * Définit une devise comme devise par défaut
     */
    public function setAsDefault(Currency $currency): void
    {
        // Désactiver toutes les autres devises par défaut
        $this->createQueryBuilder('c')
            ->update()
            ->set('c.isDefault', ':false')
            ->setParameter('false', false)
            ->getQuery()
            ->execute();

        // Activer la nouvelle devise par défaut
        $currency->setDefault(true);
        $currency->setActive(true);
        $currency->setUpdatedAt(new \DateTime());

        $this->getEntityManager()->flush();
    }

    /**
     * Met à jour les taux de change
     */
    public function updateExchangeRates(array $rates): void
    {
        foreach ($rates as $code => $rate) {
            $currency = $this->findByCode($code);
            if ($currency) {
                $currency->setExchangeRate((string)$rate);
                $currency->setLastRateUpdate(new \DateTime());
            }
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Statistiques des devises
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->count([]),
            'active' => $this->count(['isActive' => true]),
            'default' => $this->findDefault()?->getCode() ?? 'EUR',
        ];
    }
}
