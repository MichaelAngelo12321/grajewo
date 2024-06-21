<?php

namespace App\Repository;

use App\Entity\PromoItem;
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

    public function findBySlot(string $slot): ?PromoItem
    {
        try {
            return $this->createQueryBuilder('pi')
                ->andWhere('pi.position = :slot')
                ->andWhere('pi.isActive = true')
                ->setParameter('slot', $slot)
                ->orderBy('RAND()')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException|NonUniqueResultException) {
            return null;
        }
    }

    public function increaseClicks(PromoItem $item): void
    {
        $item->setClicksCount($item->getClicksCount() + 1);

        $this->_em->persist($item);
        $this->_em->flush();
    }

    public function incrementViews(PromoItem $item): void
    {
        $item->setViewsCount($item->getViewsCount() + 1);

        $this->_em->persist($item);
        $this->_em->flush();
    }
}
