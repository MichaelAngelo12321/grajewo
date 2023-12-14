<?php

namespace App\Repository;

use App\Entity\NameDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NameDay>
 *
 * @method NameDay|null find($id, $lockMode = null, $lockVersion = null)
 * @method NameDay|null findOneBy(array $criteria, array $orderBy = null)
 * @method NameDay[]    findAll()
 * @method NameDay[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NameDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NameDay::class);
    }
}
