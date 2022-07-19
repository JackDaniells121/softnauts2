<?php

namespace App\Service;

use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Request;

class DatesValidator
{
    public DateTime $reqDateFrom;
    public DateTime $reqDateTo;
    public string $from = '';
    public string $to = '';

    /**
     * @throws \Exception
     */
    public function __construct(
        Request $request
    )
    {
        $this->validate($request);
    }

    /**
     * @throws \Exception
     */
    public function validate(Request $request)
    {
        $from = $request->get('from');
        $this->reqDateFrom = $this->validateDate($from);

        $to = $request->get('to');
        $this->reqDateTo = $this->validateDate($to);

        $from = date('Y-m-d', strtotime($from));
        $to = date('Y-m-d', strtotime($to));

        if($to < $from){
            throw new \Exception('Invalid date range. \'from\' > \'to\'');
        }

        if ($from && $to) {
            $this->from = $from;
            $this->to = $to;
        }
    }

    /**
     * @throws \Exception
     */
    function validateDate($date, $format = 'Y-m-d H:i:s'): DateTime | \Exception
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

}