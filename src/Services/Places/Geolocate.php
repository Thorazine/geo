<?php

namespace Thorazine\Geo\Services\Places;

use Thorazine\Geo\Services\Locate\Connector;
use Thorazine\Geo\Services\Places\GeolocateOutput;
use MatanYadaev\EloquentSpatial\Objects\Point;

class Geolocate extends PlacesConnector
{
    private string $apiUrl = 'https://maps.googleapis.com/maps/api/place/details/json';
    private string $placeId; // ChIJrTLr-GyuEmsRBfy61i59si0
    private string $query; // midgetgolf
    private string $fields = 'address_components,geometry,opening_hours,place_id,types';
    private string $functionToCall;
    private string|null $country = null;
    private string|null $language = null;

    public function __construct()
    {
        $this->country = config('googlemaps.default_country', strtoupper(app()->getLocale()));
        $this->language = config('googlemaps.default_language', app()->getLocale());
        parent::__construct(new GeolocateOutput);
        $this->responseClass->language = $this->language;
    }

    /**************************************************************************
     *                  Public functions
     *************************************************************************/

    public function get()
    {
        $result = $this->call($this->apiUrl, $this->buildParams());
        return $result;
    }

    public function country(string $iso)
    {
        $this->functionToCall = 'country';
        $this->country = strtoupper($iso);
        $this->responseClass->country = $this->country;
        return $this;
    }

    public function place(string $placeId)
    {
        $this->functionToCall = 'place';
        $this->placeId = $placeId;
        $this->responseClass->placeId = $placeId;
        return $this;
    }

    public function query(string $query)
    {
        $this->functionToCall = 'query';
        $this->query = $query;
        $this->responseClass->query = $query;
        return $this;
    }

    public function language(string $language)
    {
        $this->language = $language;
    }

    public function has()
    {
        return $this->hasResult;
    }

    /**************************************************************************
     *                  Private functions
     *************************************************************************/

    private function buildParams()
    {
        if($this->functionToCall == 'query') {
            return $this->buildQueryParams();
        }
        else {
            throw new \Exception('Need more data to give a proper response. Please call the address, zipcode or coordinates method first.');
        }
    }

    private function buildQueryParams()
    {
        $query = str_replace(' ', '+', $this->country).'+'.str_replace(' ', '+', $this->query);
        return [
            'query' => $query,
            'place_type' => 'business',
            'fields' => $this->fields,
            'language' => $this->language,
        ];
    }
}