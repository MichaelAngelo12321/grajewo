<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PromoItem;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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

    public function findAllActive(): array
    {
        try {
            $today = new DateTimeImmutable();
            $qb = $this->createQueryBuilder('pi')
                ->andWhere('pi.isActive = true');

            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->lte('pi.startDate', ':today'),
                        $qb->expr()->isNull('pi.startDate')
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->gte('pi.endDate', ':today'),
                        $qb->expr()->isNull('pi.endDate')
                    )
                )
            )->setParameter('today', $today->format('Y-m-d'));

            return $qb->orderBy('RAND()')
                ->getQuery()
                ->getResult();
        } catch (NoResultException|NonUniqueResultException) {
            return [];
        }
    }

    public function findBySlot(string $slot): ?PromoItem
    {
        try {
            $today = new DateTimeImmutable();
            $qb = $this->createQueryBuilder('pi')
                ->andWhere('pi.position = :slot')
                ->andWhere('pi.isActive = true')
                ->setParameter('slot', $slot);

            $qb->andWhere(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->lte('pi.startDate', ':today'),
                        $qb->expr()->isNull('pi.startDate')
                    ),
                    $qb->expr()->orX(
                        $qb->expr()->gte('pi.endDate', ':today'),
                        $qb->expr()->isNull('pi.endDate')
                    )
                )
            )->setParameter('today', $today->format('Y-m-d'));

            $results = $qb->getQuery()->getResult();

            if (empty($results)) {
                return null;
            }

            return $results[array_rand($results)];
        } catch (NoResultException|NonUniqueResultException) {
            return null;
        }
    }

    public function countActivePerSlot(): array
    {
        $today = new DateTimeImmutable();
        $qb = $this->createQueryBuilder('pi')
            ->select('pi.position as slot, COUNT(pi.id) as count')
            ->andWhere('pi.isActive = true');

        $qb->andWhere(
            $qb->expr()->andX(
                $qb->expr()->orX(
                    $qb->expr()->lte('pi.startDate', ':today'),
                    $qb->expr()->isNull('pi.startDate')
                ),
                $qb->expr()->orX(
                    $qb->expr()->gte('pi.endDate', ':today'),
                    $qb->expr()->isNull('pi.endDate')
                ),
            ),
        )->setParameter('today', $today->format('Y-m-d'));

        return $qb->groupBy('pi.position')
            ->getQuery()
            ->getResult();
    }

    public function increaseClicks(PromoItem $item): void
    {
        $item->setClicksCount($item->getClicksCount() + 1);

        $this->_em->persist($item);
        $this->_em->flush();
    }

    /**
     * Bumps view counters with a single atomic UPDATE (rows locked in id order)
     * instead of a read-modify-write flush, which deadlocked under concurrent
     * page views and closed the EntityManager.
     *
     * @param int[] $ids
     */
    public function incrementViews(array $ids): void
    {
        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            return;
        }

        sort($ids);

        $this->_em->createQuery('UPDATE App\Entity\PromoItem pi SET pi.viewsCount = pi.viewsCount + 1 WHERE pi.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->execute();
    }
}
