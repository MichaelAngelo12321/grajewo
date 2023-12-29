<?php

namespace App\Repository;

use App\Entity\DailyImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyImage>
 *
 * @method DailyImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method DailyImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method DailyImage[]    findAll()
 * @method DailyImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DailyImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyImage::class);
    }
}
