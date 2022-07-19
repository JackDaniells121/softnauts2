<?php

namespace App\Controller;

use App\Repository\NBPGoldPriceRepository;
use App\Service\MissingDays;
use App\Service\NBPApiClient;
use App\Service\DatesValidator;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GoldController extends AbstractController
{
    private ObjectManager $entityManager;
    private DatesValidator $validator;
    private int $sum = 0;
    private int $daysRangeNumber = 0;

    public function __construct(
        private NBPApiClient $apiClient,
        private ManagerRegistry $doctrine,
        private MissingDays $missingDaysService,
    )
    {
        $this->entityManager = $this->doctrine->getManager();
    }

    #[Route('/api/gold', name: 'app_gold')]
    public function index(Request $request): JsonResponse
    {
        $this->validator = new DatesValidator($request);
        $from = $this->validator->from;
        $to = $this->validator->to;

        $this->missingDaysService->handleDates($from, $to);

        $cache = new NBPGoldPriceRepository($this->doctrine);
        $collection = $cache->findDateInRange($from, $to);

        foreach ($collection as $goldPrice) {
            $this->sum += $goldPrice->getPrice();
            ++$this->daysRangeNumber;
        }

        return $this->json([
            "from" => $this->validator->reqDateFrom->format('c'),
            "to" => $this->validator->reqDateTo->format('c'),
            "avg" => $this->calculateAvg()
        ]);
    }

    public function calculateAvg(): float
    {
        $calc = ($this->sum / $this->daysRangeNumber) / 100;
        return round($calc, 2);
    }

}
