<?php

namespace App\Service;

use App\Entity\NBPGoldPrice;
use App\Repository\NBPGoldPriceRepository;
use Doctrine\Persistence\ManagerRegistry;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use Doctrine\Persistence\ObjectManager;


class MissingDays
{
    private ObjectManager $entityManager;

    public function __construct(
        private NBPApiClient $apiClient,
        private ManagerRegistry $doctrine
    )
    {
        $this->entityManager = $this->doctrine->getManager();
    }

    public function handleDates($from, $to)
    {
        $missingDaysGroups = $this->getMissingDatesGroups($from, $to);

        foreach ($missingDaysGroups as $group) {
            if ($this->isWeekend($group) == false) {
                $this->fetchAndSave($group[0], end($group));
            }
        }
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

    public function isWeekend($group): bool
    {
        return count($group) == 2 && (date("w", strtotime($group[0])) == 6);
    }

    public function fetchAndSave($from, $to)
    {
        $results = $this->apiClient->fetchPrices($from, $to);

        foreach ($results as $result) {
            $this->saveGoldPrice($result);
        }

        $this->entityManager->flush();
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

}