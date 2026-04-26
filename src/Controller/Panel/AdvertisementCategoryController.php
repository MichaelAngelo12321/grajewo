<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\AdvertisementCategory;
use App\Form\AdvertisementCategoryType;
use App\Helper\Paginator;
use App\Repository\AdvertisementCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

class AdvertisementCategoryController extends AbstractController
{
    public function __construct(
        private readonly AdvertisementCategoryRepository $advertisementCategoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function list(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 20;

        $categories = $this->advertisementCategoryRepository->findBy(
            [],
            ['name' => 'ASC'],
            $itemsPerPage,
            ($page - 1) * $itemsPerPage
        );

        $totalItems = $this->advertisementCategoryRepository->count([]);

        return $this->render('panel/advertisement_category/list.html.twig', [
            'categories' => $categories,
            'paginator' => new Paginator(
                $totalItems,
                $itemsPerPage,
                $page,
                $request->getPathInfo()
            ),
        ]);
    }

    public function create(Request $request): Response
    {
        $category = new AdvertisementCategory();
        $form = $this->createForm(AdvertisementCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($category);

            $this->addFlash('success', 'Kategoria ogłoszeń została dodana.');
            return $this->redirectToRoute('panel_advertisement_category_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/advertisement_category/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Dodaj kategorię ogłoszeń',
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    public function edit(int $id, Request $request): Response
    {
        $category = $this->advertisementCategoryRepository->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Kategoria ogłoszeń nie została znaleziona.');
        }

        $form = $this->createForm(AdvertisementCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($category);

            $this->addFlash('success', 'Kategoria ogłoszeń została zaktualizowana.');
            return $this->redirectToRoute('panel_advertisement_category_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/advertisement_category/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edytuj kategorię ogłoszeń',
            'category' => $category,
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    public function delete(int $id, Request $request): Response
    {
        $category = $this->advertisementCategoryRepository->find($id);

        if ($category) {
            if ($category->getAdvertisements()->count() > 0) {
                $this->addFlash('danger', 'Nie można usunąć kategorii, która zawiera ogłoszenia.');
                return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('panel_advertisement_category_list'), Response::HTTP_SEE_OTHER);
            }

            $this->entityManager->remove($category);
            $this->entityManager->flush();
            $this->addFlash('success', 'Kategoria ogłoszeń została usunięta.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('panel_advertisement_category_list'), Response::HTTP_SEE_OTHER);
    }

    private function handleForm(AdvertisementCategory $category): void
    {
        if (!$category->getSlug()) {
            $category->setSlug(
                $this->slugger->slug($category->getName())->lower()->toString()
            );
        }
        
        if (!$category->getIconName()) {
            $category->setIconName('ti-circle');
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }
}