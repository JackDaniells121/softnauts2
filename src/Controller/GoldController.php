<?php

namespace App\Controller;

use App\Entity\NBPGoldPrice;
use App\Repository\NBPGoldPriceRepository;
use App\Service\NBPApiClient;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use NumberFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoldController extends AbstractController
{
    private DateTime $reqDateFrom;
    private DateTime $reqDateTo;
    private ObjectManager $entityManager;
    private int $sum = 0;
    private int $daysRangeNumber = 0;

    #[Route('/api/gold', name: 'app_gold')]
    public function index(Request $request, NBPApiClient $apiClient, ManagerRegistry $doctrine): JsonResponse
    {
        $valid = $this->validateRequest($request);

        $cache = new NBPGoldPriceRepository($doctrine);
        $collection = $cache->findDateInRange($valid['from'], $valid['to']);

        if (!$collection) {
            $this->entityManager = $doctrine->getManager();
            $results = $apiClient->fetchPrices($valid['from'], $valid['to']);

            foreach ($results as $result) {
                $collection[] = $this->saveGoldPrice($result);
            }
            $this->entityManager->flush();
        }

        foreach ($collection as $goldPrice) {
            $this->sum += $goldPrice->getPrice();
            ++$this->daysRangeNumber;
        }

        return $this->json([
            "from" => $this->reqDateFrom->format('c'),
            "to" => $this->reqDateTo->format('c'),
            "avg" => $this->calculateAvg()
        ]);
    }

    public function saveGoldPrice($result): NBPGoldPrice
    {
        $gold = new NBPGoldPrice();
        $date = new \DateTime($result['data']);
        $gold->setDate($date);
        $price = (int)((float)$result['cena'] * 100);
        $gold->setPrice($price);
        $this->entityManager->persist($gold);
        return $gold;
    }

    public function validateRequest(Request $request): array | \Exception
    {
        $from = $request->get('from');
        $this->reqDateFrom = $this->validateDate($from);

        $to = $request->get('to');
        $this->reqDateTo = $this->validateDate($to);

        if ($from && $to) {
            return [
                'from' => date('Y-m-d', strtotime($from)),
                'to' => date('Y-m-d', strtotime($to))
            ];
        }
    }

    function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $date = str_replace('T', ' ', $date);
        $date = str_replace('Z', '', $date);

        $d = DateTime::createFromFormat($format, $date);
        $result =  $d && $d->format($format) == $date;

        if (!$result) {
            throw new \Exception('Bad date format. Correct 2021-01-01T00:00:00Z');
        }
        else {
            $d->setTimezone(new DateTimeZone('+0200'));
            return $d;
        }
    }

    public function calculateAvg(): float
    {
        $calc = ($this->sum / $this->daysRangeNumber) / 100;
        $calc2 = round($calc, 2);
//        $calc3 = number_format($calc, 2); //gives string
        return $calc2;
    }

}
