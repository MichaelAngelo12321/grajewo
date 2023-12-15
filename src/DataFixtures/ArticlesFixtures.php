<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Article;
use App\Entity\Category;
use App\Enum\ArticleStatus;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ArticlesFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('pl_PL');
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
                $article->setAuthor($this->getReference(AppFixtures::ADMIN_USER_REFERENCE));
                $article->setCategory($category);
                $article->setCommentsNumber(0);
                $article->setContent($this->replaceWithParagraphTags($faker->paragraphs(rand(3, 15), true)));
                $article->setCreatedAt($date);
                $article->setExcerpt($faker->sentences(3, true));
                $article->setImageUrl(rand(0, 3) !== 1 ? "/media/upload/article/2023/12/img$imgNumber.jpg" : null);
                $article->setName(rtrim($faker->sentence(), '.'));
                $article->setStatus(ArticleStatus::PUBLISHED);
                $article->setUpdatedAt($date);
                $article->setUpdateAuthor($this->getReference(AppFixtures::ADMIN_USER_REFERENCE));

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
