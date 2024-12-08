<?php

declare(strict_types=1);

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
        // Najpierw pobieramy ID galerii z limitem
        $galleryIds = $this->createQueryBuilder('g')
            ->select('g.id')
            ->orderBy('g.' . key($orderBy), current($orderBy))
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        if (empty($galleryIds)) {
            return [];
        }

        // Następnie pobieramy pełne dane dla wybranych galerii
        return $this->createQueryBuilder('g')
            ->leftJoin('g.galleryImages', 'gi')
            ->addSelect('gi')
            ->where('g.id IN (:ids)')
            ->setParameter('ids', array_column($galleryIds, 'id'))
            ->orderBy('g.' . key($orderBy), current($orderBy))
            ->addOrderBy('gi.positionOrder', 'ASC')
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
