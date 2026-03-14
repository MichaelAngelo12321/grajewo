<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\CompanyCategory;
use App\Form\CompanyCategoryType;
use App\Helper\Paginator;
use App\Repository\CompanyCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\SluggerInterface;

class CompanyCategoryController extends AbstractController
{
    public function __construct(
        private readonly CompanyCategoryRepository $companyCategoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function list(Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 20;

        $categories = $this->companyCategoryRepository->findBy(
            [],
            ['name' => 'ASC'],
            $itemsPerPage,
            ($page - 1) * $itemsPerPage
        );

        $totalItems = $this->companyCategoryRepository->count([]);

        return $this->render('panel/company_category/list.html.twig', [
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
        $category = new CompanyCategory();
        $form = $this->createForm(CompanyCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($category);

            $this->addFlash('success', 'Kategoria została dodana.');
            return $this->redirectToRoute('panel_company_category_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/company_category/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Dodaj kategorię',
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    public function edit(int $id, Request $request): Response
    {
        $category = $this->companyCategoryRepository->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Kategoria nie została znaleziona.');
        }

        $form = $this->createForm(CompanyCategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleForm($category);

            $this->addFlash('success', 'Kategoria została zaktualizowana.');
            return $this->redirectToRoute('panel_company_category_list', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('panel/company_category/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Edytuj kategorię',
            'category' => $category,
        ], $form->isSubmitted() && !$form->isValid() ? new Response('', Response::HTTP_UNPROCESSABLE_ENTITY) : null);
    }

    public function delete(int $id, Request $request): Response
    {
        $category = $this->companyCategoryRepository->find($id);

        if ($category) {
            // Check if category has companies before deleting
            if ($category->getCompanies()->count() > 0) {
                $this->addFlash('error', 'Nie można usunąć kategorii, która zawiera firmy.');
                return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('panel_company_category_list'), Response::HTTP_SEE_OTHER);
            }

            $this->entityManager->remove($category);
            $this->entityManager->flush();
            $this->addFlash('success', 'Kategoria została usunięta.');
        }

        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('panel_company_category_list'), Response::HTTP_SEE_OTHER);
    }

    private function handleForm(CompanyCategory $category): void
    {
        if (!$category->getSlug()) {
            $category->setSlug(
                $this->slugger->slug($category->getName())->lower()->toString()
            );
        }

        $this->entityManager->persist($category);
        $this->entityManager->flush();
    }
}
