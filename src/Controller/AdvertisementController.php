<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Advertisement;
use App\Form\AdvertisementType;
use App\Helper\Paginator;
use App\Repository\AdvertisementRepository;
use App\Service\UserActivity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdvertisementController extends AbstractController
{
    public function __construct(
        private readonly AdvertisementRepository $advertisementRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserActivity $userActivity,
    ) {
    }

    public function add(Request $request): Response
    {
        $advertisement = new Advertisement();
        $form = $this->createForm(AdvertisementType::class, $advertisement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->userActivity->canUserPerformAction(
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
            )) {
                $this->addFlash('danger', 'Musisz poczekać 2 minuty przed dodaniem kolejnego ogłoszenia');

                return $this->redirectToRoute('advertisement_list');
            }

            $advertisement->setCreatedAt(new DateTimeImmutable());
            $advertisement->setIsActive(true);

            $this->userActivity->recordUserActivity($request->getClientIp(), $request->headers->get('User-Agent'));

            $this->entityManager->persist($advertisement);
            $this->entityManager->flush();

            $this->addFlash('success', 'Dziękujemy za dodanie ogłoszenia. Jest ono już widoczne w serwisie.');

            return $this->redirectToRoute('advertisement_list');
        }

        return $this->render('app/advertisement/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function list(Request $request, ?string $category = null): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 20;

        $criteria = ['isActive' => true];
        if ($category) {
            $criteria['category'] = $this->advertisementRepository->findCategoryBySlug($category);
        }

        $advertisements = $this->advertisementRepository->findBy(
            $criteria,
            ['createdAt' => 'DESC'],
            $itemsPerPage,
            ($page - 1) * $itemsPerPage,
        );

        return $this->render('app/advertisement/list.html.twig', [
            'advertisements' => $advertisements,
            'categories' => $this->advertisementRepository->findAllCategories(),
            'currentCategory' => $category,
            'paginator' => new Paginator(
                $this->advertisementRepository->count($criteria),
                $itemsPerPage,
                $page,
                $request->getPathInfo(),
            ),
        ]);
    }
}
