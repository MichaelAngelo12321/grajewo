<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Repository\Cached\CategoryCachedRepository;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RequestListener
{

    public function __construct(
        private string $defaultLocale,
        private CategoryCachedRepository $categoryRepository
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $event->getRequest()->setLocale($this->defaultLocale);

        $ignorePaths = [
            '/_wdt/',
            '/_profiler/',
            '/_fragment',
            '/build',
            '/media',
            '/panel',
            '/raport',
            '/kalendarz',
            '/przeslij',
            '/promo',
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
            $categoryFound = false;

            foreach ($categories as $category) {
                if ($category->getSlug() === rtrim($matches[1], '/')) {
                    $request->attributes->set('category', $category);
                    $categoryFound = true;
                    break;
                }
            }

            if (!$categoryFound) {
                throw new NotFoundHttpException();
            }
        }
    }

}
