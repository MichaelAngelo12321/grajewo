<?php

namespace App\Repository;

use App\Entity\PharmacyDuty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PharmacyDuty>
 *
 * @method PharmacyDuty|null find($id, $lockMode = null, $lockVersion = null)
 * @method PharmacyDuty|null findOneBy(array $criteria, array $orderBy = null)
 * @method PharmacyDuty[]    findAll()
 * @method PharmacyDuty[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PharmacyDutyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PharmacyDuty::class);
    }
}
