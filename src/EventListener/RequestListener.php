<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Repository\CategoryCachedRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RequestListener
{

    public function __construct(
        private string $defaultLocale,
        private CategoryCachedRepository $categoryRepository
    ) {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $event->getRequest()->setLocale($this->defaultLocale);

        $ignorePaths = [
            '/_wdt/',
            '/_profiler/',
            '/_fragment',
        ];
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        foreach ($ignorePaths as $ignorePath) {
            if (str_starts_with($pathInfo, $ignorePath)) {
                return;
            }
        }

        if (preg_match('#^/([^/]+)#', $pathInfo, $matches)) {
            $categories = $this->categoryRepository->findAll();

            foreach ($categories as $category) {
                if ($category->getSlug() === rtrim($matches[1], '/')) {
                    $request->attributes->set('category', $category);
                    break;
                }
            }
        }
    }

}
