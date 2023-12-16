<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserReport;
use App\Form\UserReportType;
use App\Repository\UserReportRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserReportController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserReportRepository $userReportRepository
    ) {
    }

    public function add(Request $request): Response
    {
        $report = new UserReport();
        $form = $this->createForm(UserReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report->setIpAddress($request->getClientIp());
            $report->setCreatedAt(new DateTimeImmutable());

            $this->entityManager->persist($report);
            $this->entityManager->flush();

            $this->addFlash('success', 'Dziękujemy za przesłanie raportu');

            return $this->redirectToRoute('user_report');
        }

        return $this->render('app/user_report/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function index(): Response
    {
        $userReports = $this->userReportRepository->findBy([], ['createdAt' => 'DESC'], 30);

        return $this->render('app/user_report/index.html.twig', [
            'reports' => $userReports
        ]);
    }
}
