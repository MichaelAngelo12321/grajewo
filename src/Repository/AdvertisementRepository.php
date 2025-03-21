<?php

namespace App\Repository;

use App\Entity\Advertisement;
use App\Entity\AdvertisementCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Advertisement>
 *
 * @method Advertisement|null find($id, $lockMode = null, $lockVersion = null)
 * @method Advertisement|null findOneBy(array $criteria, array $orderBy = null)
 * @method Advertisement[]    findAll()
 * @method Advertisement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdvertisementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Advertisement::class);
    }

    public function findAllCategories(): array
    {
        return $this->getEntityManager()
            ->getRepository(AdvertisementCategory::class)
            ->findAll();
    }

    public function findCategoryBySlug(string $slug): ?AdvertisementCategory
    {
        return $this->getEntityManager()
            ->getRepository(AdvertisementCategory::class)
            ->findOneBy(['slug' => $slug]);
    }

    public function findLatestAdvertisements(int $limit): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isActive = true')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
