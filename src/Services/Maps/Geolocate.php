<?php

namespace Thorazine\Geo\Services\Maps;

use Thorazine\Geo\Services\Locate\Connector;
use Thorazine\Geo\Services\Maps\GeolocateOutput;
use MatanYadaev\EloquentSpatial\Objects\Point;

class Geolocate extends MapsConnector
{
    private string $apiUrl = 'https://maps.googleapis.com/maps/api/geocode/json';
    private string $functionToCall;
    private float|null $lat = null;
    private float|null $lng = null;
    private string|null $zipcode = null;
    private string|null $number = null;
    private string|null $city = null;
    private string|null $street = null;
    private string|null $country = null;
    private string|null $province = null;
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

    public function province(string $province)
    {
        $this->functionToCall = 'province';
        $this->province = $province;
        $this->responseClass->province = $this->province;
        return $this;
    }

    public function address(string $city, string $street = null, string|null $number = null)
    {
        $this->functionToCall = 'address';
        $this->city = $city;
        $this->street = $street;
        $this->number = $number;
        $this->responseClass->city = $city;
        $this->responseClass->street = $street;
        $this->responseClass->number = $number;
        return $this;
    }

    public function zipcode(string $zipcode, string $number)
    {
        $this->functionToCall = 'zipcode';
        $this->zipcode = $zipcode;
        $this->number = $number;
        $this->responseClass->zipcode = $zipcode;
        $this->responseClass->number = $number;
        return $this;
    }

    public function coordinates(float $lat, float $lng)
    {
        $this->functionToCall = 'coordinates';
        $this->lat = $lat;
        $this->lng = $lng;
        $this->responseClass->lat = $lat;
        $this->responseClass->lng = $lng;
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
        if($this->functionToCall == 'address') {
            return $this->buildAddressParams();
        }
        elseif($this->functionToCall == 'province') {
            return $this->buildProvinceParams();
        }
        elseif($this->functionToCall == 'country') {
            return $this->buildCountryParams();
        }
        elseif($this->functionToCall == 'coordinates') {
            return $this->buildCoordinatesParams();
        }
        elseif($this->functionToCall == 'zipcode') {
            return $this->buildZipcodeParams();
        }
        else {
            throw new \Exception('Need more data to give a proper response. Please call the address, zipcode or coordinates method first.');
        }
    }

    private function buildCountryParams()
    {
        $address = str_replace(' ', '+', $this->country);
        return [
            'address' => $address,
            'place_type' => 'region',
            'language' => $this->language,
        ];
    }

    private function buildProvinceParams()
    {
        $address = str_replace(' ', '+', $this->province);
        return [
            'address' => $address,
            'place_type' => 'locality',
            'language' => $this->language,
        ];
    }

    private function buildAddressParams()
    {
        $address = implode('+', [
            urlencode($this->street),
            urlencode($this->number),
        ]);
        $address .= ','.str_replace(' ', '+', $this->city);
        if($this->province) {
            $address .= ','.str_replace(' ', '+', $this->province);
        }
        return [
            'address' => $address,
            'region' => $this->country,
            'language' => $this->language,
        ];
    }

    private function buildCoordinatesParams()
    {
        $latlng = implode(',', [
            $this->lat,
            $this->lng,
        ]);
        return [
            'latlng' => $latlng,
            'language' => $this->language,
        ];
    }

    private function buildZipcodeParams()
    {
        $address = implode(',', [
            $this->zipcode,
            $this->number,
        ]);
        return [
            'address' => $address,
            'region' => $this->country,
            'language' => $this->language,
        ];
    }

    
}