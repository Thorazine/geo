<?php

namespace Thorazine\Geo\Services\Geolocate;

use Illuminate\Filesystem\Filesystem;

class GeolocateOutput extends GeoOutput
{
    public float|null $lat = null;
    public float|null $lng = null;
    public float|null $accuracy = null;

    public function setResponse($fullResponse) : self
    {
        $this->result = $fullResponse;
        $this->lat = $fullResponse->location->lat;
        $this->lng = $fullResponse->location->lng;
        $this->accuracy = $fullResponse->accuracy;
        $this->hasResult = true;
        
        return $this;
    }

    public function has()
    {
        return $this->hasResult;
    }
}