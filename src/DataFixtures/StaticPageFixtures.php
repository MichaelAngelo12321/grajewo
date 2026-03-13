<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\StaticPage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class StaticPageFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['static'];
    }

    public function load(ObjectManager $manager): void
    {
        $pages = [
            [
                'title' => 'O nas',
                'slug' => 'o-nas',
                'content' => '<p>Jesteśmy lokalnym portalem informacyjnym Info24.</p>',
            ],
            [
                'title' => 'Regulamin',
                'slug' => 'regulamin',
                'content' => '<p>Treść regulaminu serwisu Info24.</p>',
            ],
            [
                'title' => 'Kontakt',
                'slug' => 'kontakt',
                'content' => '<p>Skontaktuj się z nami: kontakt@info24.pl</p>',
            ],
            [
                'title' => 'Polityka prywatności',
                'slug' => 'polityka-prywatnosci',
                'content' => '<p>Treść polityki prywatności serwisu Info24.</p>',
            ],
            [
                'title' => 'Reklama',
                'slug' => 'reklama',
                'content' => '<p>Informacje o reklamie w serwisie Info24.</p>',
            ],
        ];

        foreach ($pages as $pageData) {
            $page = new StaticPage();
            $page->setTitle($pageData['title']);
            $page->setSlug($pageData['slug']);
            $page->setContent($pageData['content']);
            
            $manager->persist($page);
        }

        $manager->flush();
    }
}
