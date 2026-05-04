<?php

namespace App\Command;

use App\Entity\Company;
use App\Entity\CompanyCategory;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:import-companies',
    description: 'Imports companies and their categories from the legacy database.',
)]
class ImportCompaniesCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ManagerRegistry $registry;

    public function __construct(EntityManagerInterface $entityManager, ManagerRegistry $registry)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->registry = $registry;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');
        $this->entityManager->getConnection()->getConfiguration()->setSQLLogger(null);
        
        $io = new SymfonyStyle($input, $output);
        $legacyDb = $this->registry->getConnection('legacy');
        $legacyDb->getConfiguration()->setSQLLogger(null);
        
        $this->entityManager->getConnection()->getConfiguration()->setMiddlewares([]);
        $legacyDb->getConfiguration()->setMiddlewares([]);

        $slugger = new AsciiSlugger();

        $io->info('Fetching company categories from legacy database...');
        
        // Zauważ że w dumpie tabela ma literówkę w nazwie: 'companies_categoies'
        $categoriesData = $legacyDb->fetchAllAssociative('SELECT * FROM companies_categoies');
        $categoriesMap = []; // old_id => CompanyCategory entity
        
        foreach ($categoriesData as $catRow) {
            $catId = (int)$catRow['id'];
            $title = $catRow['title'] ?? 'Kategoria Firmy ' . $catId;
            $slug = $catRow['slug'] ?? strtolower($slugger->slug($title)->toString());
            
            $category = $this->entityManager->getRepository(CompanyCategory::class)->findOneBy(['slug' => $slug]);
            
            if (!$category) {
                $category = new CompanyCategory();
                $category->setName($title);
                $category->setSlug($slug);
                // Domyślne wartości
                if (method_exists($category, 'setPositionOrder')) {
                    $category->setPositionOrder($catId);
                }
                $this->entityManager->persist($category);
            }
            $categoriesMap[$catId] = $category;
        }
        $this->entityManager->flush();

        // Mapowanie plików (logotypy firm)
        $io->info('Fetching image relations for companies...');
        $morphData = $legacyDb->fetchAllAssociative("SELECT * FROM upload_file_morph WHERE related_type = 'companies' AND field = 'logo'");
        $companyImages = []; 
        $allFileIds = [];
        
        foreach ($morphData as $row) {
            $companyId = (int)$row['related_id'];
            $fileId = (int)$row['upload_file_id'];
            $allFileIds[] = $fileId;
            $companyImages[$companyId] = $fileId;
        }

        $io->info('Fetching image URLs...');
        $filesUrlMap = [];
        $allFileIds = array_unique($allFileIds);
        
        if (!empty($allFileIds)) {
            $chunks = array_chunk($allFileIds, 1000);
            foreach ($chunks as $chunk) {
                $idsList = implode(',', $chunk);
                $fileData = $legacyDb->fetchAllAssociative("SELECT id, url, name FROM upload_file WHERE id IN ($idsList)");
                foreach ($fileData as $row) {
                    $fileName = basename($row['url'] ?? $row['name']);
                    $filesUrlMap[(int)$row['id']] = $fileName;
                }
            }
        }

        $io->info('Fetching companies from legacy database...');

        $defaultUser = $this->entityManager->getRepository(User::class)->findOneBy([]);

        $limit = 500;
        $offset = 0;
        $totalMigrated = 0;

        while (true) {
            $companiesData = $legacyDb->fetchAllAssociative("SELECT * FROM companies LIMIT $limit OFFSET $offset");
            
            if (empty($companiesData)) {
                break;
            }

            foreach ($companiesData as $row) {
                $companyId = (int)$row['id'];
                
                // Szukamy firmy czy już nie istnieje by jej nie powielić 
                $title = $row['title'] ?? 'Firma ' . $companyId;
                $company = $this->entityManager->getRepository(Company::class)->findOneBy(['name' => mb_substr($title, 0, 255)]);
                
                if (!$company) {
                    $company = new Company();
                    $company->setName(mb_substr($title, 0, 255));
                    
                    $slug = strtolower($slugger->slug($title)->toString());
                    // Zabezpieczenie przed zduplikowanym slugiem
                    $existingSlug = $this->entityManager->getRepository(Company::class)->findOneBy(['slug' => $slug]);
                    if ($existingSlug) {
                        $slug = $slug . '-' . uniqid();
                    }
                    $company->setSlug($slug);
                }

                $company->setDescription($row['content'] ?? '');
                $company->setEmail(mb_substr($row['email'] ?? '', 0, 255));
                $company->setPhone(mb_substr($row['phone'] ?? '', 0, 255));
                $company->setWebsite(mb_substr($row['url'] ?? '', 0, 255));
                
                // Miejscowość
                if (method_exists($company, 'setCity')) {
                    $company->setCity(mb_substr($row['location'] ?? 'Ełk', 0, 255));
                }

                // Daty
                $createdAt = !empty($row['created_at']) ? new \DateTimeImmutable($row['created_at']) : new \DateTimeImmutable();
                $company->setCreatedAt($createdAt);
                
                $updatedAt = !empty($row['updated_at']) ? new \DateTimeImmutable($row['updated_at']) : new \DateTimeImmutable();
                $company->setUpdatedAt($updatedAt);
                
                $company->setIsActive(!empty($row['published_at']));
                
                // Promowana domyślnie false (zależnie czy masz taką kolumnę w legacy)
                $company->setIsPromoted(false);

                // Autor
                if ($defaultUser) {
                    if (method_exists($company, 'setAuthor')) {
                        $company->setAuthor($defaultUser);
                    }
                    if (method_exists($company, 'setUpdateAuthor')) {
                        $company->setUpdateAuthor($defaultUser);
                    }
                }

                // Kategoria
                $oldCatId = (int)($row['category'] ?? $row['categoy'] ?? 0); // w dumpie było "categoy" i "category"
                if (isset($categoriesMap[$oldCatId])) {
                    $company->setCategory($categoriesMap[$oldCatId]);
                }

                // Logo
                $year = $createdAt->format('Y');
                $month = $createdAt->format('m');
                
                if (isset($companyImages[$companyId])) {
                    $fileId = $companyImages[$companyId];
                    if (isset($filesUrlMap[$fileId])) {
                        // Budujemy ścieżkę według standardu nowej bazy
                        $logoPath = sprintf('/media/upload/company/%s/%s/%s', $year, $month, $filesUrlMap[$fileId]);
                        $company->setLogo($logoPath);
                    }
                }

                $this->entityManager->persist($company);
                $totalMigrated++;
            }

            $this->entityManager->flush();
            $this->entityManager->clear();
            
            // Odtwarzanie odpiętych encji po clear()
            $defaultUser = $defaultUser ? $this->entityManager->getRepository(User::class)->find($defaultUser->getId()) : null;
            
            $categoriesMap = []; 
            $allCategoriesData = $legacyDb->fetchAllAssociative('SELECT * FROM companies_categoies');
            foreach ($allCategoriesData as $catRow) {
                $catId = (int)$catRow['id'];
                $category = $this->entityManager->getRepository(CompanyCategory::class)->findOneBy(['slug' => $catRow['slug']]);
                if ($category) {
                    $categoriesMap[$catId] = $category;
                }
            }

            $offset += $limit;
            $io->text("Migrated $totalMigrated companies...");
        }

        $io->success(sprintf('Successfully imported %d companies and their categories!', $totalMigrated));

        return Command::SUCCESS;
    }
}