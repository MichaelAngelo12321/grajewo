<?php

require __DIR__.'/vendor/autoload.php';
require __DIR__.'/config/bootstrap.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\User;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();
$hasher = $container->get('security.user_password_hasher');

$user = new User();
$user->setEmail('admin@g24.pl');
$user->setRoles(['ROLE_ADMIN']);
$user->setPassword($hasher->hashPassword($user, 'admin'));
$user->setFullName('Admin G24');
$user->setPosition('Administrator');
$user->setCreatedAt(new \DateTimeImmutable());
$user->setIsActive(true);

$em->persist($user);
$em->flush();

echo "User created successfully.\n";
