<?php

namespace App\Repository;

use App\Entity\Gallery;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Gallery>
 *
 * @method Gallery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Gallery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Gallery[]    findAll()
 * @method Gallery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GalleryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Gallery::class);
    }

    public function findAllWithFirstImage(
        array $criteria = [],
        array $orderBy = ['createdAt' => 'DESC'],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.galleryImages', 'gi')
            ->addSelect('gi')
            ->orderBy('g.' . key($orderBy), current($orderBy))
            ->addOrderBy('gi.positionOrder', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findWithSortedImages(int $id): ?Gallery
    {
        return $this->createQueryBuilder('g')
            ->leftJoin('g.galleryImages', 'gi')
            ->addSelect('gi')
            ->where('g.id = :id')
            ->setParameter('id', $id)
            ->orderBy('gi.positionOrder', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
