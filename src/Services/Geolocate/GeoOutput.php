<?php

namespace Thorazine\Geo\Services\Geolocate;

use ReflectionProperty;

abstract class GeoOutput
{
    protected $result;
    protected $hasResult = false;
    
    abstract public function setResponse($response) : self;

    public function __get($name)
    {
        if(property_exists($this, $name) && (new ReflectionProperty($this, $name))->isPublic()) {
            return $this->$name;
        }
    }
}