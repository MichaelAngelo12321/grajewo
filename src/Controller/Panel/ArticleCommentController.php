<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Article;
use App\Repository\ArticleCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ArticleCommentController extends AbstractController
{
    public function __construct(
        private ArticleCommentRepository $articleCommentRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function list(): Response
    {
        return $this->render('panel/article_comment/list.html.twig', [
            'comments' => $this->articleCommentRepository->findPending(),
        ]);
    }

    public function hideComment(int $commentId, Request $request): Response
    {
        $comment = $this->articleCommentRepository->find($commentId);

        if ($comment === null) {
            throw $this->createNotFoundException();
        }

        if (!$comment->isIsHidden()) {
            $article = $comment->getArticle();
            $article->setCommentsNumber(max(0, $article->getCommentsNumber() - 1));
            $this->entityManager->persist($article);
        }

        $comment->setIsHidden(true);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Komentarz został ukryty');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    public function unlockComment(int $commentId, Request $request): Response
    {
        $comment = $this->articleCommentRepository->find($commentId);

        if ($comment === null) {
            throw $this->createNotFoundException();
        }

        if ($comment->isIsHidden()) {
            $article = $comment->getArticle();
            $article->setCommentsNumber($article->getCommentsNumber() + 1);
            $this->entityManager->persist($article);
        }

        $comment->setIsHidden(false);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Komentarz został zaakceptowany');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }

    public function deleteComment(int $commentId, Request $request): Response
    {
        $comment = $this->articleCommentRepository->find($commentId);

        if ($comment === null) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Komentarz został usunięty');

        return $this->redirect($request->headers->get('referer'), Response::HTTP_SEE_OTHER);
    }
}
