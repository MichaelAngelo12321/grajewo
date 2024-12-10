<?php

namespace App\Repository;

use App\Entity\AdvertisementCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdvertisementCategory>
 *
 * @method AdvertisementCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdvertisementCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdvertisementCategory[]    findAll()
 * @method AdvertisementCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdvertisementCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdvertisementCategory::class);
    }
}
