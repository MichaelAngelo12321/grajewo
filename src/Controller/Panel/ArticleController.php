<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Article;
use App\Enum\ArticleStatus;
use App\Enum\UploadDirectory;
use App\Form\ArticleEditType;
use App\Form\ArticleType;
use App\Helper\Paginator;
use App\Repository\ArticleRepository;
use App\Repository\Cached\CacheKeyPrefix;
use App\Repository\Cached\CategoryCachedRepository;
use App\Repository\CategoryRepository;
use App\Service\FileCleaner;
use App\Service\FileUploader;
use App\Service\ImageResizer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private CacheItemPoolInterface $cachePool,
        private CategoryCachedRepository $categoryCachedRepository,
        private CategoryRepository $categoryRepository,
        private EntityManagerInterface $entityManager,
        private FileCleaner $fileCleaner,
        private FileUploader $fileUploader,
        private ImageResizer $imageResizer,
    ) {
    }

    public function changeStatus(int $id, int $status, Request $request): Response
    {
        $article = $this->articleRepository->find($id);

        if ($article === null) {
            throw $this->createNotFoundException();
        }

        $article->setStatus(ArticleStatus::from($status));

        $this->entityManager->persist($article);
        $this->entityManager->flush();
        $this->clearCache();

        $this->addFlash('success', 'Status został zmieniony');

        return $request->headers->has('referer')
            ? $this->redirect($request->headers->get('referer'))
            : $this->redirectToRoute('panel_article_list');
    }

    private function clearCache(): void
    {
        $this->cachePool->clear(CacheKeyPrefix::ARTICLE_LATEST_FROM_CATEGORY);
        $this->cachePool->clear(CacheKeyPrefix::ARTICLE_MOST_POPULAR);
    }


    public function create(Request $request): Response
    {
        $categories = $this->categoryCachedRepository->findAll();

        $article = new Article();
        $articleForm = $this->createForm(ArticleType::class, $article);
        $articleForm->handleRequest($request);

        if ($articleForm->isSubmitted() && $articleForm->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $articleForm->get('imageUrl')->getData();

            if ($imageFile !== null) {
                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::ARTICLE);
                $this->imageResizer->resize($imageFileName);

                $article->setImageUrl($imageFileName);
            }

            $article->setCreatedAt(new DateTimeImmutable());
            $article->setUpdatedAt(new DateTimeImmutable());

            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $this->clearCache();

            $this->addFlash('success', 'Artykuł został dodany');

            return $this->redirectToRoute('panel_article_list');
        }

        return $this->render('panel/article/create.html.twig', [
            'categories' => $categories,
            'form' => $articleForm->createView(),
        ]);
    }

    public function delete(int $id, Request $request): Response
    {
        $article = $this->articleRepository->find($id);

        if ($article === null) {
            throw $this->createNotFoundException();
        }

        $articleCategory = $article->getCategory();

        if ($article->getImageUrl()) {
            $this->fileCleaner->removeFile($article->getImageUrl());
        }

        $this->entityManager->remove($article);
        $this->entityManager->flush();

        $this->clearCache();

        $this->addFlash('success', 'Artykuł został usunięty');

        return $request->headers->has('referer')
            ? $this->redirect($request->headers->get('referer'))
            : $this->redirectToRoute('panel_article_list');
    }

    public function edit(int $id, Request $request): Response
    {
        $article = $this->articleRepository->find($id);

        if ($article === null) {
            throw $this->createNotFoundException();
        }

        $categories = $this->categoryCachedRepository->findAll();

        $articleImage = $article->getImageUrl();
        $articleForm = $this->createForm(ArticleEditType::class, $article);
        $articleForm->handleRequest($request);

        if ($articleForm->isSubmitted() && $articleForm->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $articleForm->get('imageUrl')->getData();

            if ($imageFile !== null) {
                if ($articleImage) {
                    $this->fileCleaner->removeFile($articleImage);
                }

                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::ARTICLE);
                $this->imageResizer->resize($imageFileName);

                $article->setImageUrl($imageFileName);
            } elseif ($articleForm->get('hasChangedImageUrl')->getData()) {
                $article->setImageUrl(null);
                $this->fileCleaner->removeFile($articleImage);
            }

            $article->setUpdatedAt(new DateTimeImmutable());

            $this->entityManager->persist($article);
            $this->entityManager->flush();

            $this->clearCache();

            $this->addFlash('success', 'Artykuł został zaktualizowany');

            return $this->redirectToRoute('panel_article_list');
        }

        return $this->render('panel/article/edit.html.twig', [
            'article' => $article,
            'categories' => $categories,
            'form' => $articleForm->createView(),
        ]);
    }

    public function list(Request $request): Response
    {
        $articlesNumber = (int) $request->get('number', 15);
        $page = (int) $request->get('page', 1);
        $criteria = [];

        if ($request->get('category', '') !== '') {
            $criteria['category'] = $request->get('category');
        }

        if ($request->get('search_query', '') !== '') {
            $criteria['name'] = ['FULLTEXT', $request->get('search_query')];
        }

        $articles = $this->articleRepository->findBy(
            $criteria,
            ['id' => 'DESC'],
            $articlesNumber,
            ($page - 1) * $articlesNumber,
        );
        $categories = $this->categoryCachedRepository->findAll();

        return $this->render('panel/article/list.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
            'paginator' => new Paginator(
                $this->articleRepository->count($criteria),
                $articlesNumber,
                $page,
                $request->getUri(),
            ),
        ]);
    }
}
