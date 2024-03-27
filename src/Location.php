<?php

namespace Thorazine\Geo;

use GuzzleHttp\Client;

class Location
{
    public function byCity(string $country, string $city, string|null $province = null)
    {
        $params = [
            'country' => $country,
            'city' => $city,
            'province' => $province,
        ];
        return $this->get('city', $params);
    }

    public function byCoordinates(float $latitude, float $longitude)
    {
        $params = [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
        return $this->get('city', $params);
    }

    private function get($path = '', $params = [])
    {
        $client = new Client([
            'timeout'  => 2.0,
            'base_uri' => env('GEO_API_BASE_URL', (config('app.env') == 'local') ? 'http://activity.eventadoo.test/api/' : throw new \Exception('No GEO_API_BASE_URL found in .env')),
        ]);

        $params = array_merge($params, [
            'key' => env('GEO_API_KEY', (config('app.env') == 'local') ? 'cRF8ISlKpZrzdIZHe8jL7IMQSLMOejibFycKUbek' : throw new \Exception('No GEO_API_KEY found in .env')),
        ]);

        $response = $client->request('GET', $path, [
            'query' => $params,
        ]);

        return json_decode($response->getBody()->getContents());
    }
}