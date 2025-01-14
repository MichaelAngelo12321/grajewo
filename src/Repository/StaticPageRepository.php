<?php

namespace App\Repository;

use App\Entity\StaticPage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * @extends ServiceEntityRepository<StaticPage>
 *
 * @method StaticPage|null find($id, $lockMode = null, $lockVersion = null)
 * @method StaticPage|null findOneBy(array $criteria, array $orderBy = null)
 * @method StaticPage[]    findAll()
 * @method StaticPage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StaticPageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaticPage::class);
    }

    public function generateUniqueSlug(string $title): string
    {
        $slugger = new AsciiSlugger();
        $slug = $slugger->slug($title)->lower();

        $i = 1;
        $originalSlug = $slug;

        while ($this->findOneBy(['slug' => $slug]) !== null) {
            $slug = $originalSlug . '-' . $i++;
        }

        return $slug;
    }
}
