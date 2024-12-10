<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserReport;
use App\Enum\UploadDirectory;
use App\Form\UserReportType;
use App\Repository\UserReportRepository;
use App\Service\FileUploader;
use App\Service\ImageResizer;
use App\Service\UserActivity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserReportController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileUploader $fileUploader,
        private ImageResizer $imageResizer,
        private UserActivity $userActivity,
        private UserReportRepository $userReportRepository
    ) {
    }

    public function add(Request $request): Response
    {
        $report = new UserReport();
        $form = $this->createForm(UserReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->userActivity->canUserPerformAction(
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
            )) {
                $this->addFlash('danger', 'Musisz poczekać 2 minuty przed dodaniem kolejnej treści');

                return $this->redirectToRoute('user_report_add');
            }

            $report->setIpAddress($request->getClientIp());
            $report->setCreatedAt(new DateTimeImmutable());

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile !== null) {
                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::USER_REPORT);
                $this->imageResizer->resize($imageFileName);

                $report->setImageUrl($imageFileName);
            }

            $this->userActivity->recordUserActivity($request->getClientIp(), $request->headers->get('User-Agent'));

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

        return $this->render('app/user_report/list.html.twig', [
            'reports' => $userReports
        ]);
    }
}
