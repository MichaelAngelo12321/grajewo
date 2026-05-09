<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Form\PanelUserReportType;
use App\Repository\Cached\CacheKeyPrefix;
use App\Repository\UserReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;

class UserReportController extends AbstractController
{
    public function __construct(
        private CacheInterface $cache,
        private EntityManagerInterface $entityManager,
        private UserReportRepository $userReportRepository,
    ) {
    }

    public function editReport(int $reportId, Request $request): Response
    {
        $report = $this->userReportRepository->find($reportId);

        if ($report === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(PanelUserReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->cache->delete(CacheKeyPrefix::USER_REPORT_LAST . '5');
            $this->addFlash('success', 'Raport został zaktualizowany');

            return $this->redirectToRoute('user_report', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/user_report/edit.html.twig', [
            'report' => $report,
            'form' => $form->createView(),
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    public function publishReport(int $reportId, Request $request): Response
    {
        $report = $this->userReportRepository->find($reportId);

        if ($report === null) {
            throw $this->createNotFoundException();
        }

        $report->setIsActive(true);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        $this->cache->delete(CacheKeyPrefix::USER_REPORT_LAST . '5');
        $this->addFlash('success', 'Raport został zaakceptowany i opublikowany');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    public function deleteReport(int $reportId, Request $request): Response
    {
        $report = $this->userReportRepository->find($reportId);

        if ($report === null) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($report);
        $this->entityManager->flush();

        $this->cache->delete(CacheKeyPrefix::USER_REPORT_LAST . '5');
        $this->addFlash('success', 'Raport został usunięty');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    public function hideReport(int $reportId, Request $request): Response
    {
        $report = $this->userReportRepository->find($reportId);

        if ($report === null) {
            throw $this->createNotFoundException();
        }

        $report->setIsHidden(true);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        $this->cache->delete(CacheKeyPrefix::USER_REPORT_LAST);
        $this->addFlash('success', 'Raport został ukryty');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    public function unlockReport(int $reportId, Request $request): Response
    {
        $report = $this->userReportRepository->find($reportId);

        if ($report === null) {
            throw $this->createNotFoundException();
        }

        $report->setIsHidden(false);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        $this->cache->delete(CacheKeyPrefix::USER_REPORT_LAST);
        $this->addFlash('success', 'Raport został odblokowany');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }
}
