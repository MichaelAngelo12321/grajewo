<?php

namespace App\Repository;

use App\Entity\GasStation;
use App\Entity\GasStationPrice;
use DateTimeImmutable;
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

    public function findLatestStationPrice(GasStation $station): array
    {
        $queryBuilder = $this->createQueryBuilder('gsp');

        return $queryBuilder
            ->select('gsp')
            ->where('gsp.station = :stationId')
            ->setParameter('stationId', $station)
            ->andWhere('DATE(gsp.date) >= DATE(:date)')
            ->setParameter('date', new DateTimeImmutable('-1 day'))
            ->orderBy('gsp.date', 'DESC')
            ->getQuery()->getResult();
    }

    public function findTodayStationPrices(GasStation $station, ?string $priceType = null): array|null|GasStationPrice
    {
        $queryBuilder = $this->createQueryBuilder('gsp');

        $query = $queryBuilder
            ->select('gsp')
            ->where('gsp.station = :stationId')
            ->setParameter('stationId', $station)
            ->andWhere('DATE(gsp.date) = DATE(:date)')
            ->setParameter('date', new DateTimeImmutable())
            ->orderBy('gsp.date', 'DESC');

        if ($priceType) {
            $query->andWhere('gsp.type = :priceType')->setParameter('priceType', $priceType);

            return $query->getQuery()->getOneOrNullResult();
        }


        return $query->getQuery()->getResult();
    }
}
