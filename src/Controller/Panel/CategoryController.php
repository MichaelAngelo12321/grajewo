<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\ArticleRepository;
use App\Repository\Cached\CacheKeyPrefix;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;

class CategoryController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private CacheInterface $cache,
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

        return $this->json(['success' => true]);
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

            $this->addFlash('success', 'Kategoria została dodana');

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

        $categoryArticles = $this->articleRepository->count(['category' => $category]);

        if ($categoryArticles > 0) {
            $this->addFlash('danger', 'Nie można usunąć kategorii, która posiada artykuły');

            return $this->redirectToRoute('panel_categories_list');
        }

        $this->entityManager->remove($category);

        $categories = $this->categoryRepository->findBy(['isRoot' => false], ['positionOrder' => 'ASC']);
        $positionOrder = 0;

        foreach ($categories as $cat) {
            if ($cat->getId() === $id) {
                continue;
            }

            $cat->setPositionOrder($positionOrder);
            $this->entityManager->persist($cat);
            $positionOrder++;
        }

        $this->entityManager->flush();

        $this->cache->delete(CacheKeyPrefix::CATEGORY_ALL);
        $this->cache->delete(CacheKeyPrefix::CATEGORY_TOP);
        $this->cache->delete(CacheKeyPrefix::ARTICLE_LATEST_FROM_CATEGORY);

        $this->addFlash('success', 'Kategoria została usunięta');

        return $this->redirectToRoute('panel_categories_list');
    }

    public function edit(int $id, Request $request): Response
    {
        $category = $this->categoryRepository->find($id);

        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $articlesNumber = $this->articleRepository->count(['category' => $category]);
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($category);
            $this->entityManager->flush();

            $this->addFlash('success', 'Kategoria została zaktualizowana');

            return $this->redirectToRoute('panel_categories_list');
        }

        return $this->render('panel/category/edit.html.twig', [
            'articlesNumber' => $articlesNumber,
            'category' => $category,
            'categories' => $this->categoryRepository->findBy(['isRoot' => false], ['positionOrder' => 'ASC']),
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

    public function moveArticles(int $fromCategoryId, int $toCategoryId): Response
    {
        $editFormRedirection = $this->redirectToRoute('panel_categories_edit', [
            'id' => $fromCategoryId,
        ]);
        $fromCategory = $this->categoryRepository->find($fromCategoryId);

        if ($fromCategory === null) {
            $this->addFlash('danger', 'Kategoria z której przenoszone są artykuły nie istnieje');

            return $editFormRedirection;
        }

        $toCategory = $this->categoryRepository->find($toCategoryId);

        if ($toCategory === null) {
            $this->addFlash('danger', 'Kategoria do której przenoszone są artykuły nie istnieje');

            return $editFormRedirection;
        }

        $this->categoryRepository->moveArticlesFromCategoryTo($fromCategory, $toCategory);
        $this->cache->delete(CacheKeyPrefix::ARTICLE_LATEST_FROM_CATEGORY);
        $this->cache->delete(CacheKeyPrefix::CATEGORY_ALL);
        $this->cache->delete(CacheKeyPrefix::CATEGORY_TOP);

        $this->addFlash('success', 'Artykuły zostały przeniesione');

        return $editFormRedirection;
    }
}
