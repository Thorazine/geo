<?php

namespace Thorazine\Geo;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Thorazine\Geo\Models\City;
use Thorazine\Geo\Models\Country;
use Thorazine\Geo\Models\Province;
use Thorazine\Geo\Jobs\GeocodeCity;
use Illuminate\Support\Facades\Cache;
use Thorazine\Geo\Jobs\GeocodeProvince;

class Geo
{
    private $maxDistanceForCity = 100;
    private $ipFields = ['continent','country_code','city','region','region_iso_code','latitude','longitude', 'security'];
    /**
     * Geolocate location by ip
     */
    public function ip($ip, $output = ['continent','country_code','city','region','region_iso_code','latitude','longitude'])
    {
        $ip = (! in_array($ip, ['127.0.0.1', '::1'])) ? $ip : config('geo.default_ip');

        $response = Cache::remember('ip.'.$ip, 60 * 60 * 24 * 30, function() use ($ip) {    
            $r = $this->getIpData($ip);

            if($r->city) {
                $city = (new Geo)->city($r->country_code, $r->region, $r->region_iso_code, $r->city);
                $r->city_hash = $city->hash; 
            }

            if(trans()->has('geo::geo.'.$r->region)) {
                $r->region = __('geo::geo.'.$r->region);
            }

            unset($r->security);
            return $r;
        });

        foreach(array_diff($this->ipFields, $output) as $field) {
            unset($response->$field);
        }
        return $response;
    }

    /**
     * Find the country
     */
    public function country($findCountry)
    {
        $findCountry = $this->prepare($findCountry);

        return Cache::remember('country.'.$findCountry, 60 * 60, function() use ($findCountry) {
            if(is_numeric($findCountry) && $country = Country::find($findCountry)) {
                return $country;
            } 
            return Country::where('title', $findCountry)->firstOrFail();
        });
    }

    /**
     * Find or add the province
     */
    public function province($findCountry, $findProvince, $shortProvince = null)
    {
        $findCountry = $this->prepare($findCountry);
        $findProvince = $this->prepare($findProvince);
        
        return Cache::remember('province.'.$findCountry.'.'.$findProvince, 60 * 60, function() use ($findCountry, $findProvince, $shortProvince) {

            $country = $this->country($findCountry);

            if(is_numeric($findProvince) && $province = Province::find($findProvince)) {
                return $province;
            }
            elseif($province = Province::where('country_id', $country->id)->where('search_title', Str::ascii($findProvince))->first()) {
                $province->setRelation('country', $country);
                return $province;
            }

            $province = new Province;
            $province->country_id = $country->id;
            $province->title = $findProvince;
            $province->search_title = Str::ascii($findProvince);
            if($shortProvince) {
                $province->title_short = $shortProvince;
            }
            $province->slug = $this->slug($findProvince, '-');
            $province->save();

            GeocodeProvince::dispatch($province);

            return $province;
        });
    }

    /**
     * Find or add the city
     */
    public function city($findCountry, $findProvince, $shortProvince = null, $findCity, $lat = null, $lng = null)
    {
        $findCountry = $this->prepare($findCountry);
        $findProvince = $this->prepare($findProvince);
        $findCity = $this->prepare($findCity);

        return Cache::remember('city.'.$findCountry.'.'.$findProvince.'.'.$findCity, 60 * 60, function() use ($findCountry, $findProvince, $shortProvince, $findCity, $lat, $lng) {

            $country = $this->country($findCountry);
            $province = $this->province($country->id, $findProvince, $shortProvince);

            if(is_numeric($findCity) && $city = City::find($findCity)) {
                $city->setRelation('country', $country);
                $city->setRelation('province', $province);
                return $city;
            }
            elseif($province && $city = City::where('country_id', $country->id)->where('province_id', $province->id)->where('search_title', $this->ascii($findCity))->first()) {
                $city->setRelation('country', $country);
                $city->setRelation('province', $province);
                return $city;
            }
            elseif($lat && $lng && $city = City::where('country_id', $country->id)
            ->whereRaw('SOUNDEX(title) = SOUNDEX(?)', [$findCity])
            ->whereDistance('location', $lat, $lng, $this->maxDistanceForCity)
            ->orderByDistance('location', $lat, $lng)
            ->first()) {
                $city->setRelation('country', $country);
                $city->setRelation('province', $province);
                return $city;
            }
            elseif($city = $this->soundexMatch(City::where('country_id', $country->id)
            ->whereRaw('SOUNDEX(search_title) = SOUNDEX(?)', [$this->ascii($findCity)])->get(), $findCity)) {
                $city->setRelation('country', $country);
                $city->setRelation('province', $province);
                return $city;
            }

            $city = new City;
            $city->country_id = $country->id;
            $city->province_id = $province->id;
            $city->title = $findCity;
            $city->slug = $this->slug($findCity, '-');
            $city->search_title = $this->ascii($findCity);
            $city->save();
            $city->setRelation('country', $country);
            $city->setRelation('province', $province);

            GeocodeCity::dispatch($city);

            return $city;
        });
    }

    /**
     * Prepare the value
     */
    private function prepare($value)
    {
        $value = preg_replace('/\(.*\)/', '', $value); // Remove anything in brackets
        $value = trim($value);
        return $value;
    }

    /**
     * Create a slug
     */
    private function slug($value, $separator = '-')
    {
        return Str::slug($value, $separator);
    }

    /**
     * Create an ascii string
     */
    private function ascii($value)
    {
        return Str::ascii($value);
    }


    private function transform($value)
    {
        if($trans = config('transform.'.$value)) {
            return $trans;
        }
        return $value;
    }


    private function soundexMatch($results, $search, $maxDistance = 3, $column = 'title')
    {
        $smallest = 100;
        $selected = null;
        foreach($results as $key => $result) {
            $distance = levenshtein($result->{$column}, $search);
            if($distance < $maxDistance && $distance < $smallest) {
                $smallest = $distance;
                $selected = $key;
            }
        }

        if($selected) {
            return $result[$selected];
        }
        return null;
    }

    private function getIpData($ip)
    {
        $client = new Client([
            'timeout'  => 2.0,
        ]);

        $params = array_merge([], [
            'api_key' => config('geo.iplocation_api_key'),
            'ip_address' => $ip,
            'fields' => implode(',', $this->ipFields),
        ]);

        $response = $client->request('GET', 'https://ipgeolocation.abstractapi.com/v1/', [
            'query' => $params,
        ]);

        $r = json_decode($response->getBody()->getContents());
        
        $r->city = $this->prepare($r->city);

        return $r;
    }
}