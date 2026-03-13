<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Poll;
use App\Entity\PollVote;
use App\Repository\PollOptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PollController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PollOptionRepository $pollOptionRepository,
    ) {
    }

    #[Route('/ankieta/glosuj/{id}', name: 'poll_vote', methods: ['POST'])]
    public function vote(Request $request, Poll $poll): Response
    {
        if (!$poll->isIsActive()) {
            $this->addFlash('danger', 'Ta ankieta jest już nieaktywna.');
            return $this->redirect($request->headers->get('referer'));
        }

        $optionId = $request->request->get('option');
        if (!$optionId) {
            $this->addFlash('danger', 'Musisz wybrać jedną z opcji.');
            return $this->redirect($request->headers->get('referer'));
        }

        $option = $this->pollOptionRepository->find($optionId);
        if (!$option || $option->getPoll() !== $poll) {
            $this->addFlash('danger', 'Nieprawidłowa opcja.');
            return $this->redirect($request->headers->get('referer'));
        }

        $cookieName = 'poll_voted_' . $poll->getId();
        if ($request->cookies->has($cookieName)) {
            $this->addFlash('warning', 'Już głosowałeś w tej ankiecie.');
            return $this->redirect($request->headers->get('referer'));
        }

        $vote = new PollVote();
        $vote->setPoll($poll);
        $vote->setOption($option);
        $vote->setIpAddress($request->getClientIp());

        $option->increaseVotesCount();

        $this->entityManager->persist($vote);
        $this->entityManager->flush();

        $response = $this->redirect($request->headers->get('referer'));
        $response->headers->setCookie(new Cookie(
            $cookieName,
            '1',
            time() + (86400 * 30)
        ));

        $this->addFlash('success', 'Twój głos został oddany.');

        return $response;
    }
}
