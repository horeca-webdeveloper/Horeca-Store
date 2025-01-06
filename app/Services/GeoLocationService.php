<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GeoLocationService
{
    protected $client;
    protected $apiUrl = 'http://ip-api.com/json/'; // Using ip-api.com

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getLocation($ip)
    {
        try {
            $response = $this->client->get($this->apiUrl . $ip);
            $data = json_decode($response->getBody(), true);

            return $data;
        } catch (RequestException $e) {
            // Handle error
            return null;
        }
    }
}
