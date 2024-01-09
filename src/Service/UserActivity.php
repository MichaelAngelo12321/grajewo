<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\UserLastActivity;
use App\Repository\UserLastActivityRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserActivity
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserLastActivityRepository $userLastActivityRepository
    ) {
    }

    public function canUserPerformAction(string $ipAddress, string $userAgent): bool
    {
        $lastActivity = $this->userLastActivityRepository->findOneBy(
            ['ipAddress' => $ipAddress, 'userAgent' => $userAgent],
        );

        if (!$lastActivity) {
            return true;
        }

        $currentTimestamp = time();
        $lastActivityTimestamp = $lastActivity->getTimestamp();

        $differenceInMinutes = ($currentTimestamp - $lastActivityTimestamp) / 60;

        return $differenceInMinutes >= 2;
    }

    public function recordUserActivity(string $ipAddress, string $userAgent): void
    {
        $lastActivity = $this->userLastActivityRepository->findOneBy(
            ['ipAddress' => $ipAddress, 'userAgent' => $userAgent],
        );

        if (!$lastActivity) {
            $lastActivity = new UserLastActivity();
            $lastActivity->setIpAddress($ipAddress);
            $lastActivity->setUserAgent($userAgent);
        }

        $lastActivity->setTimestamp((string)time());

        $this->entityManager->persist($lastActivity);
        $this->entityManager->flush();
    }
}
