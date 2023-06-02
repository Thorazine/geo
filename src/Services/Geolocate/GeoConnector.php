<?php

namespace Thorazine\Geo\Services\Geolocate;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GeoConnector
{
    private Client $client;
    protected array $defaultTemplate = [];
    private string $apiUrl = 'https://maps.googleapis.com/maps/api/';
    protected $responseClass;
    protected bool $hasResult = false;


    public function __construct($responseClass)
    {
        if(!config('geo.google_maps_api_key')) {
            throw new \Exception('Google maps key not set (GOOGLE_MAPS_API_KEY in env)');
        }

        $this->responseClass = $responseClass;

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout'  => 2.0,
        ]);
    }

    final protected function call($url, $params = [])
    {        
        $this->hasResult = false;

        $params = array_merge($params, [
            'key' => config('geo.google_maps_api_key'),
        ]);

        $response = $this->client->request('POST', $url, [
            'query' => $params,
        ]);

        $response = json_decode($response->getBody()->getContents());

        // if($response->status == 'ZERO_RESULTS') {
        //     return $this->responseClass->setResponse($response);
        // }
        // if($response->status != 'OK') {
        //     Log::error('Google maps error: '.$response->error_message);
        //     throw new \Exception('Google maps error: '.$response->status.'. See logs for more information');
        // }

        $this->hasResult = true;
        return $this->responseClass->setResponse($response);
    }
    
}