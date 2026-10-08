<?php

namespace App\Repository;

use App\Entity\Advertisement;
use App\Entity\AdvertisementCategory;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
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
    /** Advertisements older than this are hidden and removed by app:advertisement:cleanup. */
    public const LIFETIME = '-30 days';

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

    public static function expiryDate(): DateTimeImmutable
    {
        return new DateTimeImmutable(self::LIFETIME);
    }

    /**
     * findBy() equivalent that skips expired advertisements.
     *
     * @return Advertisement[]
     */
    public function findNotExpiredBy(array $criteria, array $orderBy, ?int $limit = null, ?int $offset = null): array
    {
        return $this->matching(
            $this->notExpiredCriteria($criteria)->orderBy($orderBy)->setMaxResults($limit)->setFirstResult($offset)
        )->toArray();
    }

    public function countNotExpiredBy(array $criteria): int
    {
        return $this->matching($this->notExpiredCriteria($criteria))->count();
    }

    private function notExpiredCriteria(array $criteria): Criteria
    {
        $result = Criteria::create()->where(Criteria::expr()->gte('createdAt', self::expiryDate()));
        foreach ($criteria as $field => $value) {
            $result->andWhere(Criteria::expr()->eq($field, $value));
        }

        return $result;
    }

    private function createActiveQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.isActive = true')
            ->andWhere('a.createdAt >= :expiryDate')
            ->setParameter('expiryDate', self::expiryDate());
    }

    public function findLatestAdvertisements(int $limit): array
    {
        return $this->createActiveQueryBuilder()
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPromotedAdvertisements(int $limit = 5): array
    {
        return $this->createActiveQueryBuilder()
            ->andWhere('a.isPromoted = true')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function increaseBatchViews(array $advertisements): void
    {
        if (empty($advertisements)) {
            return;
        }

        foreach ($advertisements as $advertisement) {
            $advertisement->setViews($advertisement->getViews() + 1);
            $this->getEntityManager()->persist($advertisement);
        }

        $this->getEntityManager()->flush();
    }
}
