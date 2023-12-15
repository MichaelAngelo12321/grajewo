<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 *
 * @method Setting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Setting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Setting[]    findAll()
 * @method Setting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Setting::class);
    }

    public function set(string $name, string $value): void
    {
        $setting = $this->findOneBy(['name' => $name]);

        if (!$setting) {
            $setting = new Setting();
            $setting->setName($name);
        }

        $setting->setValue($value);

        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
    }
}
