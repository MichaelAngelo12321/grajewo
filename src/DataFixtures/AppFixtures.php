<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use App\Enum\ArticleStatus;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $this->entityManager->getConfiguration()->getEntityListenerResolver()->clear();

        $faker = \Faker\Factory::create('pl_PL');
        $categories = [
            'Kategoria główna' => 'info',
            'Aktualności' => 'aktualnosci',
            'Kultura' => 'kultura',
            'Sport' => 'sport',
            'Wydarzenia' => 'wydarzenia',
            'Artykuły i felietony' => 'artykuly',
            'Oferty i promocje' => 'oferty',
        ];
        $categoryColors = [
            'primary',
            'secondary',
            'success',
            'danger',
            'warning',
            'info',
            'green',
            'green-dark',
            'blue',
        ];

        $admin = new User();
        $admin->setCreatedAt(new DateTimeImmutable());
        $admin->setEmail('admin@g24.pl');
        $admin->setFullName('Andrzej Waliwąs');
        $admin->setImagePath('https://thispersondoesnotexist.com');
        $admin->setPassword($this->passwordHasherFactory->getPasswordHasher(User::class)->hash('admin'));
        $admin->setPosition('Administrator');
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        $articlesPerDay = 3;

        foreach ($categories as $name => $slug) {
            $articlesNumber = rand(175, 300);
            $startDate = (new DateTimeImmutable())->modify('-' . intdiv($articlesNumber, $articlesPerDay) . ' days');
            $articleCounter = 0;

            $category = new Category();
            $category->setColor($categoryColors[array_rand($categoryColors)]);
            $category->setIsRoot($slug === 'info');
            $category->setName($name);
            $category->setPositionOrder($slug === 'aktualnosci' || $slug === 'info' ? 0 : 1);
            $category->setSlug($slug);
            $manager->persist($category);

            if ($slug === 'info') {
                continue;
            }

            for ($i = 0; $i < $articlesNumber; $i++) {
                $date = $startDate->modify('+' . intdiv($articleCounter, $articlesPerDay) . ' days');
                $date = $date->setTime(rand(8, 19), rand(0, 59));

                $imgNumber = rand(1, 65);
                $article = new Article();
                $article->setAuthor($admin);
                $article->setCategory($category);
                $article->setCommentsNumber(0);
                $article->setContent($this->replaceWithParagraphTags($faker->paragraphs(rand(3, 15), true)));
                $article->setCreatedAt($date);
                $article->setExcerpt($faker->sentences(3, true));
                $article->setImageUrl(rand(0, 3) !== 1 ? "/media/upload/article/2023/12/img$imgNumber.jpg" : null);
                $article->setName(rtrim($faker->sentence(), '.'));
                $article->setStatus(ArticleStatus::PUBLISHED);
                $article->setUpdatedAt($date);
                $article->setUpdateAuthor($admin);

                $manager->persist($article);
                $articleCounter++;
            }

            $manager->flush();
        }

        shell_exec('php bin/console cache:clear');
    }

    private function replaceWithParagraphTags(string $text): string
    {
        $text = str_replace("\n", '</p><p>', $text);

        return "<p>$text</p>";
    }
}
