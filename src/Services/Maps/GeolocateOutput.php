<?php

namespace Thorazine\Geo\Services\Maps;

use Illuminate\Filesystem\Filesystem;

class GeolocateOutput extends MapsOutput
{
    public string|null $country = null;
    public string|null $countryIso = null;
    public string|null $province = null;
    public string|null $provinceIso = null;
    public string|null $city = null;
    public string|null $street = null;
    public string|null $number = null;
    public string|null $zipcode = null;
    public float|null $lat = null;
    public float|null $lng = null;
    public string|null $language = null;

    public function setResponse($fullResponse) : self
    {
        if($result = @$fullResponse->results[0]->address_components) {
            $this->hasResult = true;
            $this->result = $result;
        }

        if($geometry = @$fullResponse->results[0]->geometry) {
            $this->lat = $this->lat ?: $geometry->location->lat;
            $this->lng = $this->lng ?: $geometry->location->lng;
        }

        return $this;
    }

    public function has()
    {
        return $this->hasResult;
    }

    public function parse()
    {
        $this->find('country', 'country', 'short_name', function($valueShort, $valueLong) {
            $this->country = __('geo::countries.'.$valueShort);
            $this->countryIso = $valueShort;
        });

        $province = [
            'NL' => 'administrative_area_level_1',
            'BE' => 'administrative_area_level_2',
        ];

        $al = $province[$this->countryIso] ?? 'administrative_area_level_1';

        $this->find('province', $al, 'short_name', function($valueShort, $valueLong) {
            if(! trans()->has('provinces.'.$this->countryIso.'.'.$valueShort)) {
                $this->updateTranslationFile(base_path('lang/nl/provinces.php'), '#'.$this->countryIso.'#', "'".$valueShort."' => '".$valueLong."',");
                $this->province = $valueLong;
                $this->provinceIso = $valueShort;
            }
            else {
                $this->province = trans('provinces.'.$this->countryIso.'.'.$valueShort);
                $this->provinceIso = $valueShort;
            }
        });
        $this->find('city', ['locality', 'administrative_area_level_2'], 'short_name');
        $this->find('street', 'route', 'short_name');
        $this->find('number', 'street_number', 'short_name');
        $this->find('zipcode', 'postal_code', 'long_name');

        return $this;
    }

    public function toArray()
    {
        return [
            'country' => $this->country,
            'countryIso' => $this->countryIso,
            'province' => $this->province,
            'provinceIso' => $this->provinceIso,
            'city' => $this->city,
            'street' => $this->street,
            'number' => $this->number,
            'zipcode' => $this->zipcode,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'language' => $this->language,
        ];
    }

    private function find(string $parameter, array|string $arguments, $key = 'long_name', $callback = null)
    {
        if($this->$parameter) {
            return $this->$parameter;
        }

        if(is_string($arguments)) {
            $arguments = [$arguments];
        }

        foreach($arguments as $argument) {
            foreach($this->result as $component) {
                if(in_array($argument, $component->types)) {
                    if(is_callable($callback)) {
                        return $callback($component->short_name, $component->long_name);
                    }
                    else {
                        $this->$parameter = @$component->$key;
                    }
                    return $this->$parameter;
                }
            }
        }
        return null;
    }

    private function updateTranslationFile($path, $replaceKey, $add)
    {
        $fs = new Filesystem;

        if($fs->exists($path)) {
            $content = $fs->get($path);
            $add = $add.PHP_EOL.'        '.$replaceKey;
            $content = str_replace($replaceKey, $add, $content);
            $fs->put($path, $content);
        }
    }
}