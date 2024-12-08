<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class AppFixtures extends Fixture
{
    public const ADMIN_USER_REFERENCE = 'admin-user';

    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setCreatedAt(new DateTimeImmutable());
        $admin->setEmail('admin@g24.pl');
        $admin->setFullName('Andrzej Waliwąs');
        $admin->setImagePath('https://thispersondoesnotexist.com');
        $admin->setPassword($this->passwordHasherFactory->getPasswordHasher(User::class)->hash('admin'));
        $admin->setPosition('Administrator');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsActive(true);

        $manager->persist($admin);
        $manager->flush();

        $this->addReference(self::ADMIN_USER_REFERENCE, $admin);
    }
}
