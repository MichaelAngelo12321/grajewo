<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Form\AdvertisementType;
use App\Helper\Paginator;
use App\Repository\AdvertisementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdvertisementController extends AbstractController
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function list(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 15;

        $advertisements = $this->advertisementRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            $itemsPerPage,
            ($page - 1) * $itemsPerPage,
        );

        $totalItems = $this->advertisementRepository->count([]);

        return $this->render('panel/advertisement/list.html.twig', [
            'advertisements' => $advertisements,
            'paginator' => new Paginator($totalItems, $itemsPerPage, $page, $request->getPathInfo()),
        ]);
    }

    public function edit(int $advertisementId, Request $request): Response
    {
        $advertisement = $this->advertisementRepository->find($advertisementId);

        if ($advertisement === null) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(AdvertisementType::class, $advertisement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Ogłoszenie zostało zaktualizowane');

            return $this->redirectToRoute('panel_advertisement_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/advertisement/edit.html.twig', [
            'advertisement' => $advertisement,
            'form' => $form->createView(),
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    public function remove(int $advertisementId, Request $request): Response
    {
        $advertisement = $this->advertisementRepository->find($advertisementId);

        if ($advertisement === null) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($advertisement);
        $this->entityManager->flush();

        $this->addFlash('success', 'Ogłoszenie zostało usunięte');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    public function togglePromoted(int $advertisementId, Request $request): Response
    {
        $advertisement = $this->advertisementRepository->find($advertisementId);

        if ($advertisement === null) {
            throw $this->createNotFoundException();
        }

        $advertisement->setIsPromoted(!$advertisement->isPromoted());
        $this->entityManager->flush();

        $this->addFlash('success', 'Status promowania został zmieniony.');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    public function toggleActive(int $advertisementId, Request $request): Response
    {
        $advertisement = $this->advertisementRepository->find($advertisementId);

        if ($advertisement === null) {
            throw $this->createNotFoundException();
        }

        $advertisement->setIsActive(!$advertisement->isIsActive());
        $this->entityManager->flush();

        $this->addFlash('success', 'Status aktywności został zmieniony.');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }
}
