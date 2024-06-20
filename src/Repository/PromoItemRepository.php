<?php

namespace App\Repository;

use App\Entity\PromoItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PromoItem>
 *
 * @method PromoItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method PromoItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method PromoItem[]    findAll()
 * @method PromoItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PromoItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromoItem::class);
    }
}
