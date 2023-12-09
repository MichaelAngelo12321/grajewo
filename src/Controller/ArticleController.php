<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ArticleComment;
use App\Entity\Category;
use App\Enum\ArticleStatus;
use App\Form\CommentType;
use App\Helper\Paginator;
use App\Repository\ArticleRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function details(string $slug, int $id, Category $category, Request $request): Response
    {
        $article = $this->articleRepository->find($id);

        if (!$article || $article->getStatus() !== ArticleStatus::PUBLISHED) {
            throw $this->createNotFoundException('Article not found');
        }

        $this->articleRepository->increaseViewsNumber($article);

        $comment = new ArticleComment();
        $commentForm = $this->createForm(CommentType::class, $comment);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid() && !$article->isHasCommentsDisabled()) {
            $comment->setArticle($article);
            $comment->setIpAddress($request->getClientIp());
            $comment->setCreatedAt(new DateTimeImmutable());

            $article->setCommentsNumber($article->getCommentsNumber() + 1);

            $this->entityManager->persist($article);
            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $this->addFlash('success', 'Twój komentarz został dodany');

            return $this->redirectToRoute('article_details', [
                '_fragment' => 'comment-' . $comment->getId(),
                '__category' => $category->getSlug(),
                'id' => $id,
                'slug' => $slug,
            ]);
        }

        return $this->render('app/article/details.html.twig', [
            'article' => $article,
            'category' => $category,
            'commentForm' => $commentForm->createView(),
            'id' => $id,
            'slug' => $slug,
        ]);
    }

    public function list(Category $category, Request $request): Response
    {
        $currentPage = $request->query->getInt('page', 1);
        $itemsPerPage = 10;
        $articles = $this->articleRepository->findLatestByCategory(
            $category,
            $itemsPerPage,
            ($currentPage - 1) * $itemsPerPage
        );

        return $this->render('app/article/list.html.twig', [
            'articles' => $articles,
            'category' => $category,
            'paginator' => new Paginator(
                $this->articleRepository->count(['category' => $category]),
                $itemsPerPage,
                $currentPage,
                $request->getPathInfo(),
            ),
        ]);
    }
}
