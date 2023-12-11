<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\Cached\CacheKeyPrefix;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractController
{
    public function __construct(
        private CacheItemPoolInterface $cachePool,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function changeOrder(Request $request): Response
    {
        $requestData = json_decode($request->getContent(), true);
        $elementsOrder = $requestData['elementsOrder'];

        foreach ($elementsOrder as $elementId => $order) {
            $category = $this->categoryRepository->find($elementId);
            $category->setPositionOrder($order);
            $this->entityManager->persist($category);
        }

        $this->entityManager->flush();
        $this->clearCache();

        return $this->json(['success' => true]);
    }

    private function clearCache(): void
    {
        $this->cachePool->clear(CacheKeyPrefix::CATEGORY_ALL);
        $this->cachePool->clear(CacheKeyPrefix::CATEGORY_TOP);
    }

    public function create(Request $request): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lastCategory = $this->categoryRepository->findOneBy([], ['positionOrder' => 'DESC']);

            $category->setPositionOrder($lastCategory ? $lastCategory->getPositionOrder() + 1 : 1);

            $this->entityManager->persist($category);
            $this->entityManager->flush();
            $this->clearCache();

            return $this->redirectToRoute('panel_categories_list');
        }

        return $this->render('panel/category/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function delete(int $id): Response
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $this->entityManager->remove($category);

        $categories = $this->categoryRepository->findBy(['isRoot' => false], ['positionOrder' => 'ASC']);
        $positionOrder = 0;

        foreach ($categories as $category) {
            $category->setPositionOrder($positionOrder);
            $this->entityManager->persist($category);
            $positionOrder++;
        }

        $this->entityManager->flush();
        $this->clearCache();

        return $this->redirect($this->generateUrl('panel_categories_list'));
    }

    public function edit(int $id, Request $request): Response
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($category);
            $this->entityManager->flush();
            $this->clearCache();

            return $this->redirectToRoute('panel_categories_list');
        }

        return $this->render('panel/category/edit.html.twig', [
            'category' => $category,
            'form' => $form->createView(),
        ]);
    }

    public function list(): Response
    {
        $categories = $this->categoryRepository->findBy([], ['positionOrder' => 'ASC']);

        return $this->render('panel/category/list.html.twig', [
            'categories' => $categories,
        ]);
    }
}
