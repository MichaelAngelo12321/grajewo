<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Gallery;
use App\Entity\GalleryImage;
use App\Entity\User;
use App\Repository\ArticleRepository;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class GalleryFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private ArticleRepository $articleRepository,
    ) {
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class,
            ArticlesFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('pl_PL');

        for ($i = 0; $i < 20; $i++) {
            $gallery = new Gallery();
            $gallery->setName($faker->sentence(3));
            $gallery->setCreatedAt(new DateTimeImmutable());
            $gallery->setUpdatedAt(new DateTimeImmutable());
            $gallery->setAuthor($this->getReference(AppFixtures::ADMIN_USER_REFERENCE, User::class));
            $gallery->setUpdateAuthor($this->getReference(AppFixtures::ADMIN_USER_REFERENCE, User::class));

            $imagesCount = rand(5, 15);
            for ($j = 0; $j < $imagesCount; $j++) {
                $imgNumber = rand(1, 65);

                $image = new GalleryImage();
                $image->setImageUrl("/media/upload/article/2023/12/img$imgNumber.jpg");
                $image->setDescription($faker->sentence());
                $image->setPositionOrder($j);
                $image->setGallery($gallery);

                $manager->persist($image);
            }

            $manager->persist($gallery);
        }

        $manager->flush();

        $articles = $this->articleRepository->findBy([], ['createdAt' => 'DESC'], 10);
        $galleries = $manager->getRepository(Gallery::class)->findAll();

        foreach ($articles as $index => $article) {
            if (isset($galleries[$index])) {
                $article->setGallery($galleries[$index]);
                $manager->persist($article);
            }
        }

        $manager->flush();
    }
}
