<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Advertisement;
use App\Entity\AdvertisementCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Monolog\DateTimeImmutable;
use Symfony\Component\String\Slugger\AsciiSlugger;

class AdvertisementFixtures extends Fixture
{
    private const CATEGORIES = [
        'Motoryzacja' => 'car',
        'Nieruchomości' => 'home',
        'Praca' => 'briefcase',
        'Usługi' => 'tools',
        'Dom i ogród' => 'home-eco',
        'Elektronika' => 'computer',
        'Sport i hobby' => 'play-basketball',
        'Moda' => 'hanger',
        'Zwierzęta' => 'paw',
        'Dla dzieci' => 'baby-toy',
    ];

    public function load(ObjectManager $manager): void
    {
        $slugger = new AsciiSlugger();
        $faker = Factory::create('pl_PL');

        foreach (self::CATEGORIES as $categoryName => $iconName) {
            $category = new AdvertisementCategory();
            $category->setName($categoryName);
            $category->setSlug(strtolower($slugger->slug($categoryName)->toString()));
            $category->setIconName($iconName);

            for ($i = 0; $i < 15; $i++) {
                $advertisement = new Advertisement();
                $advertisement->setCategory($category);
                $advertisement->setTitle($faker->sentence(3));
                $advertisement->setContent($faker->sentence(15));
                $advertisement->setAuthor($faker->firstName);
                $advertisement->setEmail($faker->email);
                $advertisement->setPhone(str_replace(' ', '', $faker->phoneNumber));
                $advertisement->setIsActive(true);
                $advertisement->setCreatedAt(DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-1 year')));
                $advertisement->setIpAddress($faker->ipv4);

                $manager->persist($advertisement);
            }

            $manager->persist($category);
        }

        $manager->flush();
    }
}
