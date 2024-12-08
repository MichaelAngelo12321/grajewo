<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DailyVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DailyVideo>
 *
 * @method DailyVideo|null find($id, $lockMode = null, $lockVersion = null)
 * @method DailyVideo|null findOneBy(array $criteria, array $orderBy = null)
 * @method DailyVideo[]    findAll()
 * @method DailyVideo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DailyVideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DailyVideo::class);
    }
}
