<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\UserReport;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class UserReportFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $date = new DateTimeImmutable('-60 days');
        $today = new DateTimeImmutable('today');

        while ($date < $today) {
            $this->createReportsForDate($manager, $date);
            $date = $date->modify('+1 day');
        }

        $manager->flush();
    }

    private function createReportsForDate(ObjectManager $manager, DateTimeImmutable $date): void
    {
        $faker = Factory::create('pl_PL');
        $reportCount = random_int(0, 10);

        for ($i = 0; $i < $reportCount; ++$i) {
            $date = $date->setTime(rand(0, 23), rand(0, 59), rand(0, 59));

            $report = new UserReport();
            $report->setCreatedAt($date);
            $report->setAuthor($faker->userName());
            $report->setName($faker->sentence());
            $report->setContent($faker->paragraphs(random_int(1, 5), true));
            $report->setIpAddress($faker->ipv4());
            $manager->persist($report);
        }
    }
}
