<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NBPApiClient
{
    private $client;
    private $nbpUrl = 'http://api.nbp.pl/api/cenyzlota';

    public function __construct() {
        $client = HttpClient::create();
        $this->client = $client;
    }

    public function fetchPrices(string $date1, string $date2)
    {
        $response = $this->client->request(
            'GET',
            "http://api.nbp.pl/api/cenyzlota/$date1/$date2?format=json",
        );

        $statusCode = $response->getStatusCode();
        // $statusCode = 200
        if ($statusCode == 404) {
            $this->generateErrorResponse();
            return false;
        }
        $contentType = $response->getHeaders()['content-type'][0];
        $content = $response->getContent();
        $content = $response->toArray();

        return $content;
    }

    public function generateErrorResponse()
    {
        $response = new Response();

        $response->setContent('<html><body><h1>Requested date range not accessible</h1></body></html>');
        $response->setStatusCode(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);

        $response->headers->set('Content-Type', 'text/html');

        $response->send();
    }
}