<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GoldController extends AbstractController
{
    #[Route('/api/gold', name: 'app_gold')]
    public function index(): JsonResponse
    {

        // Replace this placeholder with real code

        return $this->json([
            "from" => "2021-01-04T00:00:00+02:00",
            "to" => "2021-01-29T00:00:00+02:00",
            "avg" => 12345.67
        ]);
    }
}
