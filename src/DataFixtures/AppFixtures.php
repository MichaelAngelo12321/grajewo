<?php

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Monolog\DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
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

        $admin = new User();
        $admin->setCreatedAt(new DateTimeImmutable(false));
        $admin->setEmail('admin@g24.pl');
        $admin->setFullName('Andrzej Waliwąs');
        $admin->setImagePath('https://thispersondoesnotexist.com');
        $admin->setPassword($this->passwordHasherFactory->getPasswordHasher(User::class)->hash('admin'));
        $admin->setPosition('Administrator');
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        foreach ($categories as $name => $slug) {
            $articlesNumber = rand(175, 300);
            $category = new Category();
            $category->setArticlesNumber($articlesNumber);
            $category->setIsTop($slug === 'aktualnosci');
            $category->setIsRoot($slug === 'info');
            $category->setName($name);
            $category->setSlug($slug);
            $manager->persist($category);

            for ($i = 0; $i < $articlesNumber; $i++) {
                $date = DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-2 years', 'now'));
                $article = new Article();
                $article->setAuthor($admin);
                $article->setCategory($category);
                $article->setCommentsNumber(rand(0, 23));
                $article->setContent($this->replaceWithParagraphTags($faker->paragraphs(rand(3, 14), true)));
                $article->setCreatedAt($date);
                $article->setExcerpt($faker->sentences(3, true));
                $article->setImageUrl(rand(0, 1) === 1 ? $faker->imageUrl(900, 600) : null);
                $article->setName(rtrim($faker->sentence(), '.'));
                $article->setUpdatedAt($date);

                $manager->persist($article);
            }

            $manager->flush();
        }
    }

    private function replaceWithParagraphTags(string $text): string
    {
        $text = str_replace("\n", '</p><p>', $text);

        return "<p>$text</p>";
    }
}
