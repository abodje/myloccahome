<?php

namespace App\Repository;

use App\Entity\Settings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Settings>
 */
class SettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settings::class);
    }

    /**
     * Trouve un paramètre par sa clé
     */
    public function findByKey(string $key): ?Settings
    {
        return $this->findOneBy(['settingKey' => $key]);
    }

    /**
     * Récupère la valeur d'un paramètre
     */
    public function getValue(string $key, $defaultValue = null)
    {
        $setting = $this->findByKey($key);
        return $setting ? $setting->getParsedValue() : $defaultValue;
    }

    /**
     * Définit la valeur d'un paramètre
     */
    public function setValue(string $key, $value): void
    {
        $setting = $this->findByKey($key);

        if ($setting) {
            $setting->setValue($value);
            $setting->setUpdatedAt(new \DateTime());
        } else {
            $setting = new Settings();
            $setting->setSettingKey($key)
                   ->setValue($value)
                   ->setCategory('GENERAL')
                   ->setDescription('Paramètre créé automatiquement')
                   ->setDataType('STRING');

            $this->getEntityManager()->persist($setting);
        }

        $this->getEntityManager()->flush();
    }

    /**
     * Trouve les paramètres par catégorie
     */
    public function findByCategory(string $category): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.category = :category')
            ->setParameter('category', $category)
            ->orderBy('s.settingKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les paramètres groupés par catégorie
     */
    public function findAllGroupedByCategory(): array
    {
        $settings = $this->findBy([], ['category' => 'ASC', 'settingKey' => 'ASC']);
        $grouped = [];

        foreach ($settings as $setting) {
            $category = $setting->getCategory();
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $setting;
        }

        return $grouped;
    }

    /**
     * Trouve les paramètres éditables
     */
    public function findEditable(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.isEditable = :editable')
            ->setParameter('editable', true)
            ->orderBy('s.category', 'ASC')
            ->addOrderBy('s.settingKey', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
