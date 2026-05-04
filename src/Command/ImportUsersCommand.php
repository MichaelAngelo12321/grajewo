<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-users',
    description: 'Imports administrative users from legacy database.',
)]
class ImportUsersCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    public function __construct(EntityManagerInterface $entityManager, ManagerRegistry $registry)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->registry = $registry;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $legacyDb = $this->registry->getConnection('legacy');

        $io->info('Fetching users from legacy database (strapi_administrator)...');
        
        $usersData = $legacyDb->fetchAllAssociative('SELECT * FROM strapi_administrator');
        
        $count = 0;
        foreach ($usersData as $row) {
            $email = $row['email'];
            
            // Check if user already exists
            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            
            if ($existingUser) {
                $io->note(sprintf('User %s already exists. Skipping...', $email));
                continue;
            }

            $user = new User();
            $user->setEmail($email);
            
            // We can't reuse the exact password hash if it uses a different algorithm,
            // but we can set a random one or use the legacy one if compatible. 
            // Setting a random password as they might need to reset it, or we could keep the hash if it's bcrypt.
            $user->setPassword($row['password'] ?? bin2hex(random_bytes(16)));
            
            $user->setRoles(['ROLE_ADMIN']);
            
            if (method_exists($user, 'setFirstName')) {
                $user->setFirstName($row['firstname'] ?? 'Admin');
            }
            if (method_exists($user, 'setLastName')) {
                $user->setLastName($row['lastname'] ?? 'User');
            }
            
            $name = trim(($row['firstname'] ?? '') . ' ' . ($row['lastname'] ?? ''));
            $finalName = $name ?: 'Admin User';
            
            if (method_exists($user, 'setName')) {
                $user->setName($finalName);
            }
            if (method_exists($user, 'setFullName')) {
                $user->setFullName($finalName);
            }
            if (method_exists($user, 'setPosition')) {
                $user->setPosition('Redaktor'); // Default position as it cannot be null
            }
            if (method_exists($user, 'setIsActive')) {
                $user->setIsActive((bool)($row['isActive'] ?? true));
            }
            
            if (method_exists($user, 'setCreatedAt')) {
                $user->setCreatedAt(new \DateTimeImmutable());
            }
            if (method_exists($user, 'setUpdatedAt')) {
                $user->setUpdatedAt(new \DateTimeImmutable());
            }
            
            // We need to keep track of the legacy ID somehow to map articles later.
            // Since we might not have a legacy_id column, we can rely on the fact that
            // we will fetch users by email, or we can just fetch the legacy mapping in the articles command.

            $this->entityManager->persist($user);
            $count++;
        }

        $this->entityManager->flush();

        $io->success(sprintf('Successfully imported %d users!', $count));

        return Command::SUCCESS;
    }
}