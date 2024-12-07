<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use App\Entity\User;
use App\Enum\UploadDirectory;
use App\Form\UserType;
use App\Helper\Paginator;
use App\Repository\UserRepository;
use App\Service\FileCleaner;
use App\Service\FileUploader;
use App\Service\ImageResizer;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private FileUploader $fileUploader,
        private FileCleaner $fileCleaner,
        private ImageResizer $imageResizer,
        private UserRepository $userRepository,
    ) {
    }

    public function create(Request $request): Response
    {
        $user = new User();
        $userForm = $this->createForm(UserType::class, $user);
        $userForm->handleRequest($request);

        if ($userForm->isSubmitted() && $userForm->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $userForm->get('image')->getData();

            if ($imageFile !== null) {
                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::USER);
                $this->imageResizer->resize($imageFileName, 300, 300);

                $user->setImagePath($imageFileName);
            }

            $user->setCreatedAt(new DateTimeImmutable());

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Użytkownik został dodany');

            return $this->redirectToRoute('panel_user_list');
        }

        return $this->render('panel/user/create.html.twig', [
            'userForm' => $userForm->createView(),
        ]);
    }

    public function delete(int $id, Request $request): Response
    {
        if ($this->getUser()->getId() === $id) {
            $this->addFlash('danger', 'Nie możesz usunąć samego siebie');

            return $request->headers->has('referer')
                ? $this->redirect($request->headers->get('referer'))
                : $this->redirectToRoute('panel_user_list');
        }

        $user = $this->userRepository->find($id);

        if ($user === null) {
            throw $this->createNotFoundException();
        }

        if ($user->getImagePath() !== null) {
            $this->fileCleaner->removeFile($user->getImagePath());
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->addFlash('success', 'Użytkownik został usunięty');

        return $request->headers->has('referer')
            ? $this->redirect($request->headers->get('referer'))
            : $this->redirectToRoute('panel_user_list');
    }

    public function edit(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if ($user === null) {
            throw $this->createNotFoundException();
        }

        $userForm = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $userForm->handleRequest($request);

        if ($userForm->isSubmitted() && $userForm->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $userForm->get('image')->getData();

            if ($imageFile !== null) {
                if ($user->getImagePath() !== null) {
                    $this->fileCleaner->removeFile($user->getImagePath());
                }

                $imageFileName = $this->fileUploader->upload($imageFile, UploadDirectory::USER);
                $this->imageResizer->resize($imageFileName, 300, 300);

                $user->setImagePath($imageFileName);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Użytkownik został zaktualizowany');

            return $this->redirectToRoute('panel_user_list');
        }

        return $this->render('panel/user/edit.html.twig', [
            'userForm' => $userForm->createView(),
        ]);
    }

    public function list(Request $request): Response
    {
        $itemsPerPage = (int)$request->get('number', 15);
        $page = (int)$request->get('page', 1);
        $criteria = [];
        $users = $this->userRepository->findBy($criteria, ['createdAt' => 'DESC'], $itemsPerPage, ($page - 1) * $itemsPerPage);

        return $this->render('panel/user/list.html.twig', [
            'users' => $users,
            'paginator' => new Paginator(
                $this->userRepository->count($criteria),
                $itemsPerPage,
                $page,
                $request->getUri(),
            ),
        ]);
    }
}
