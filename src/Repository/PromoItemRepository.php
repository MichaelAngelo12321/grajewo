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
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('pi.startDate'),
                        $qb->expr()->isNotNull('pi.endDate'),
                        $qb->expr()->lte('pi.startDate', ':today'),
                        $qb->expr()->gte('pi.endDate', ':today'),
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('pi.startDate'),
                        $qb->expr()->isNull('pi.endDate'),
                    ),
                ),
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
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->isNotNull('pi.startDate'),
                        $qb->expr()->isNotNull('pi.endDate'),
                        $qb->expr()->lte('pi.startDate', ':today'),
                        $qb->expr()->gte('pi.endDate', ':today'),
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->isNull('pi.startDate'),
                        $qb->expr()->isNull('pi.endDate'),
                    ),
                ),
            )->setParameter('today', $today->format('Y-m-d'));

            return $qb->orderBy('RAND()')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException|NonUniqueResultException) {
            return null;
        }
    }

    public function increaseBatchViews(array $items): void
    {
        /** @var PromoItem $item */
        foreach ($items as $item) {
            $item->setViewsCount($item->getViewsCount() + 1);

            $this->_em->persist($item);
        }

        $this->_em->flush();
    }

    public function increaseClicks(PromoItem $item): void
    {
        $item->setClicksCount($item->getClicksCount() + 1);

        $this->_em->persist($item);
        $this->_em->flush();
    }

    public function increaseViews(PromoItem $item): void
    {
        $item->setViewsCount($item->getViewsCount() + 1);

        $this->_em->persist($item);
    }
}
