<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserLastActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLastActivity>
 *
 * @method UserLastActivity|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserLastActivity|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserLastActivity[]    findAll()
 * @method UserLastActivity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserLastActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLastActivity::class);
    }
}
