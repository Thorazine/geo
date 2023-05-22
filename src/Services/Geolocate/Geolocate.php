<?php

namespace Thorazine\Geo\Services\Geolocate;

use Thorazine\Geo\Services\Geolocate\GeolocateOutput;

class Geolocate extends GeoConnector
{
    private string $apiUrl = 'https://www.googleapis.com/geolocation/v1/geolocate';

    public function __construct()
    {
        parent::__construct(new GeolocateOutput);
    }

    /**************************************************************************
     *                  Public functions
     *************************************************************************/

    public function get()
    {
        $result = $this->call($this->apiUrl, []);
        return $result;
    }

    public function has()
    {
        return $this->hasResult;
    }

    
}