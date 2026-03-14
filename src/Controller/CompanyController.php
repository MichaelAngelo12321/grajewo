<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Company;
use App\Enum\UploadDirectory;
use App\Form\CompanyFrontendType;
use App\Helper\Paginator;
use App\Repository\CompanyCategoryRepository;
use App\Repository\CompanyRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class CompanyController extends AbstractController
{
    public function __construct(
        private readonly CompanyRepository $companyRepository,
        private readonly CompanyCategoryRepository $companyCategoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileUploader $fileUploader,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('/katalog-firm/dodaj', name: 'company_add', priority: 10)]
    public function add(Request $request): Response
    {
        $company = new Company();
        $form = $this->createForm(CompanyFrontendType::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();

            if ($logoFile) {
                $newFilename = $this->fileUploader->upload($logoFile, UploadDirectory::COMPANY);
                $company->setLogo($newFilename);
            }

            // Handle slug generation
            $baseSlug = $this->slugger->slug($company->getName())->lower()->toString();
            $slug = $baseSlug;
            $counter = 1;

            while ($this->companyRepository->findOneBy(['slug' => $slug])) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $company->setSlug($slug);
            
            $company->setIsActive(false); // Default inactive
            $company->setIsPromoted(false);
            $company->setViews(0);
            $company->setCreatedAt(new \DateTimeImmutable());
            $company->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($company);
            $this->entityManager->flush();

            return $this->redirectToRoute('user_content_thank_you');
        }

        return $this->render('app/company/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/katalog-firm/{categorySlug}', name: 'company_list', defaults: ['categorySlug' => null])]
    public function list(Request $request, ?string $categorySlug = null): Response
    {
        $page = $request->query->getInt('page', 1);
        $itemsPerPage = 20;

        $criteria = ['isActive' => true];
        $currentCategory = null;

        if ($categorySlug) {
            $currentCategory = $this->companyCategoryRepository->findOneBy(['slug' => $categorySlug]);
            if ($currentCategory) {
                $criteria['category'] = $currentCategory;
            }
        }

        $promotedCompanies = $this->companyRepository->findBy(
            ['isActive' => true, 'isPromoted' => true],
            ['name' => 'ASC'],
            6
        );

        $companies = $this->companyRepository->findBy(
            $criteria,
            ['isPromoted' => 'DESC', 'name' => 'ASC'],
            $itemsPerPage,
            ($page - 1) * $itemsPerPage
        );

        $totalItems = $this->companyRepository->count($criteria);

        return $this->render('app/company/list.html.twig', [
            'promotedCompanies' => $promotedCompanies,
            'companies' => $companies,
            'categories' => $this->companyCategoryRepository->findAll(),
            'currentCategory' => $currentCategory,
            'paginator' => new Paginator(
                $totalItems,
                $itemsPerPage,
                $page,
                $request->getPathInfo()
            ),
        ]);
    }

    #[Route('/katalog-firm/szczegoly/{slug}', name: 'company_details')]
    public function details(string $slug): Response
    {
        $company = $this->companyRepository->findOneBy(['slug' => $slug, 'isActive' => true]);

        if (!$company) {
            throw $this->createNotFoundException('Firma nie została znaleziona.');
        }

        // Increment views
        $company->setViews($company->getViews() + 1);
        $this->companyRepository->save($company, true);

        return $this->render('app/company/details.html.twig', [
            'company' => $company,
        ]);
    }
}
