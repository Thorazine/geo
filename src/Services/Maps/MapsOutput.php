<?php

namespace Thorazine\Geo\Services\Maps;

use ReflectionProperty;

abstract class MapsOutput
{
    protected $result;
    protected $hasResult = false;
    
    abstract public function setResponse($response) : self;

    abstract public function parse();

    public function __get($name)
    {
        if(property_exists($this, $name) && (new ReflectionProperty($this, $name))->isPublic()) {
            return $this->$name;
        }
    }
}