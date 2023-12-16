<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Repository\UserReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class UserReportController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserReportRepository $userReportRepository,
    ) {
    }

    public function hideReport(int $reportId, Request $request)
    {
        $report = $this->userReportRepository->find($reportId);

        if ($report === null) {
            throw $this->createNotFoundException();
        }

        $report->setIsHidden(true);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        $this->addFlash('success', 'Raport został ukryty');

        return $this->redirect($request->headers->get('referer'));
    }

    public function unlockReport(int $reportId, Request $request)
    {
        $report = $this->userReportRepository->find($reportId);

        if ($report === null) {
            throw $this->createNotFoundException();
        }

        $report->setIsHidden(false);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        $this->addFlash('success', 'Raport został odblokowany');

        return $this->redirect($request->headers->get('referer'));
    }
}
