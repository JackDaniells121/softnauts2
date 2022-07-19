<?php

namespace App\Controller;

use App\Entity\NBPGoldPrice;
use App\Repository\NBPGoldPriceRepository;
use App\Service\NBPApiClient;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
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

    public function __construct(
        private NBPApiClient $apiClient,
        private ManagerRegistry $doctrine
    )
    {
        $this->entityManager = $this->doctrine->getManager();
    }

    #[Route('/api/gold', name: 'app_gold')]
    public function index(Request $request): JsonResponse
    {
        $valid = $this->validateRequest($request);

        $missingDaysGroups = $this->getMissingDatesGroups($valid['from'], $valid['to']);

        foreach ($missingDaysGroups as $group) {
            if ($this->isWeekend($group) == false) {
                $this->fetchAndSave($group[0], end($group));
            }
        }

        $cache = new NBPGoldPriceRepository($this->doctrine);
        $collection = $cache->findDateInRange($valid['from'], $valid['to']);

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

        $from = date('Y-m-d', strtotime($from));
        $to = date('Y-m-d', strtotime($to));

        if($to < $from){
             throw new \Exception('Invalid date range. \'to\' > \'from\'');
        }

        if ($from && $to) {
            return [
                'from' => $from,
                'to' => $to
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

    public function getMissingDatesGroups($from, $to)
    {
        $repo = new NBPGoldPriceRepository($this->doctrine);
        $array = $repo->findDateInRange($from, $to);
        $cache = [];

        foreach ($array as $goldPrice) {
            $date = $goldPrice->getDate()->format('Y-m-d');
            $cache[$date] = $goldPrice->getPrice();
        }

        // need to extend range by +1 to end to proper function below foreach
        $toExt = date('Y-m-d', strtotime($to . '+1 day'));

        $period = new DatePeriod(
            new DateTime($from),
            new DateInterval('P1D'),
            new DateTime($toExt)
        );

        $missingDays = [];
        $groups = 0;
        $lastDate = $from;

        foreach ($period as $key => $value) {
            $date = $value->format('Y-m-d') ;

            if (!array_key_exists($date, $cache)) {

                $next = date('Y-m-d', strtotime($lastDate . '+1 day'));

                if ($next != $date){
                    $groups++;
                }

                $missingDays[$groups][] = $date;
                $lastDate = $date;
            }
        }

        return $missingDays;
    }

    public function fetchAndSave($from, $to)
    {
        $collection = [];
        $results = $this->apiClient->fetchPrices($from, $to);

        foreach ($results as $result) {
            $collection[] = $this->saveGoldPrice($result);
        }
        $this->entityManager->flush();
//        return $collection;
    }

    public function isWeekend($group): bool
    {
        return count($group) == 2 && (date("w", strtotime($group[0])) == 6);
    }
}
