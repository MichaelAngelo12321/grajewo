<?php

declare(strict_types=1);

namespace App\Controller\Panel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DevelopmentStatusController extends AbstractController
{
    public function index()
    {
        return $this->render('panel/development_status/index.html.twig');
    }
}
