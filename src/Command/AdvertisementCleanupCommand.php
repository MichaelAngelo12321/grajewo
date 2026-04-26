<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AdvertisementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:advertisement:cleanup',
    description: 'Deletes advertisements older than 30 days',
)]
class AdvertisementCleanupCommand extends Command
{
    public function __construct(
        private AdvertisementRepository $advertisementRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $date = new \DateTimeImmutable('-30 days');
        
        $qb = $this->advertisementRepository->createQueryBuilder('a')
            ->where('a.createdAt < :date')
            ->setParameter('date', $date);
            
        $oldAds = $qb->getQuery()->getResult();
        
        $count = 0;
        foreach ($oldAds as $ad) {
            $this->entityManager->remove($ad);
            $count++;
        }
        
        $this->entityManager->flush();
        
        $output->writeln(sprintf('Usunięto %d starych ogłoszeń.', $count));

        return Command::SUCCESS;
    }
}