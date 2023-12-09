<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\Article;
use App\Repository\ArticleCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ArticleCommentController extends AbstractController
{
    public function __construct(
        private ArticleCommentRepository $articleCommentRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function hideComment(int $commentId, Request $request)
    {
        $comment = $this->articleCommentRepository->find($commentId);

        if ($comment === null) {
            throw $this->createNotFoundException();
        }

        $comment->setIsHidden(true);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Komentarz został ukryty');

        return $this->redirect($request->headers->get('referer'));
    }

    public function unlockComment(int $commentId, Request $request)
    {
        $comment = $this->articleCommentRepository->find($commentId);

        if ($comment === null) {
            throw $this->createNotFoundException();
        }

        $comment->setIsHidden(false);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Komentarz został odblokowany');

        return $this->redirect($request->headers->get('referer'));
    }
}
