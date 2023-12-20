<?php

namespace App\Repository;

use App\Entity\GasStationPrice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GasStationPrice>
 *
 * @method GasStationPrice|null find($id, $lockMode = null, $lockVersion = null)
 * @method GasStationPrice|null findOneBy(array $criteria, array $orderBy = null)
 * @method GasStationPrice[]    findAll()
 * @method GasStationPrice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GasStationPriceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GasStationPrice::class);
    }
}
