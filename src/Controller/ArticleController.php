<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ArticleComment;
use App\Entity\Category;
use App\Enum\ArticleStatus;
use App\Form\CommentType;
use App\Helper\Paginator;
use App\Repository\ArticleRepository;
use App\Repository\Cached\ArticleCachedRepository;
use App\Service\UserActivity;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ArticleCachedRepository $articleCachedRepository,
        private EntityManagerInterface $entityManager,
        private UserActivity $userActivity,
    ) {
    }

    public function details(string $slug, int $id, Request $request, ?Category $category = null): Response
    {
        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $article = $this->articleRepository->find($id);

        if (!$article || $article->getStatus() !== ArticleStatus::PUBLISHED) {
            throw $this->createNotFoundException('Article not found');
        }

        $article->setViewsNumber($article->getViewsNumber() + 1);

        if (!$article->isHasCommentsDisabled()) {
            $comment = new ArticleComment();
            $commentForm = $this->createForm(CommentType::class, $comment);
            $commentForm->handleRequest($request);

            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                if (!$this->userActivity->canUserPerformAction(
                    $request->getClientIp(),
                    $request->headers->get('User-Agent'),
                )) {
                    $this->addFlash('danger', 'Musisz poczekać 2 minuty przed dodaniem kolejnej treści');

                    return $this->redirectToRoute('article_details', [
                        '__category' => $category->getSlug(),
                        'id' => $id,
                        'slug' => $slug,
                    ]);
                }

                $comment->setArticle($article);
                $comment->setIpAddress($request->getClientIp());
                $comment->setCreatedAt(new DateTimeImmutable());

                $article->setCommentsNumber($article->getCommentsNumber() + 1);

                $this->entityManager->persist($article);
                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                $this->userActivity->recordUserActivity($request->getClientIp(), $request->headers->get('User-Agent'));

                $this->addFlash('success', 'Twój komentarz został dodany');

                return $this->redirectToRoute('article_details', [
                    '_fragment' => 'comment-' . $comment->getId(),
                    '__category' => $category->getSlug(),
                    'id' => $id,
                    'slug' => $slug,
                ]);
            }
        }

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $this->render('app/article/details.html.twig', [
            'article' => $article,
            'category' => $category,
            'commentForm' => isset($commentForm) ? $commentForm->createView() : null,
            'id' => $id,
            'slug' => $slug,
        ]);
    }

    public function eventsList(): Response
    {
        $events = $this->articleCachedRepository->findUpcomingEvents();

        return $this->render('app/article/events_list.html.twig', [
            'events' => $events,
        ]);
    }

    public function eventsListDate(string $date): Response
    {
        $events = $this->articleRepository->findEventsForDate($date);

        return $this->render('app/article/events_list_date.html.twig', [
            'date' => $date,
            'events' => $events,
        ]);
    }

    public function list(Request $request, ?Category $category = null): Response
    {
        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }

        $currentPage = $request->query->getInt('page', 1);
        $itemsPerPage = 10;
        $articles = $this->articleRepository->findLatestByCategory(
            $category,
            $itemsPerPage,
            ($currentPage - 1) * $itemsPerPage,
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
