<?php

namespace Thorazine\Geo;

use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Thorazine\Geo\Models\City;
use Thorazine\Geo\Models\Country;
use Thorazine\Geo\Models\Province;
use Thorazine\Geo\Jobs\GeocodeCity;
use Illuminate\Support\Facades\Cache;
use Thorazine\Geo\Jobs\GeocodeProvince;

class Geo
{

    public function ip($ip)
    {
        $ip = (! in_array($ip, ['127.0.0.1', '::1'])) ? $ip : config('geo.default_ip');

        $response = Cache::remember('api.location.'.$ip, 60 * 60 * 24 * 30, function() use ($ip) {
            $client = new Client([
                'timeout'  => 2.0,
            ]);
    
            $params = array_merge([], [
                'api_key' => config('geo.iplocation_api_key'),
                'ip_address' => $ip,
                'fields' => 'continent,country_code,city,region_iso_code,latitude,longitude,security',
            ]);
    
            $response = $client->request('GET', 'https://ipgeolocation.abstractapi.com/v1/', [
                'query' => $params,
            ]);
    
            $r = json_decode($response->getBody()->getContents());

            if($r->city) {
                (new Geo)->city($r->country_code, $r->region_iso_code, $r->city );
            }

            unset($r->security);
            return $r;
        });
    }


    private $maxDistanceForCity = 100;
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
            return Country::where('title', $findCountry)->first();
        });
    }

    /**
     * Find or add the province
     */
    public function province($findCountry, $findProvince)
    {
        $findCountry = $this->prepare($findCountry);
        $findProvince = $this->prepare($findProvince);
        $country = $this->country($findCountry);

        return Cache::remember('province.'.$findCountry.'.'.$findProvince, 60 * 60, function() use ($country, $findProvince) {
            

            if(is_numeric($findProvince) && $province = Province::find($findProvince)) {
                return $province;
            }
            elseif($province = Province::where('country_id', $country->id)->where('slug', $findProvince)->first()) {
                $province->setRelation('country', $country);
                return $province;
            }

            $province = new Province;
            $province->country_id = $country->id;
            $province->title = $findProvince;
            $province->slug = $this->slug($findProvince, '-');
            $province->save();

            GeocodeProvince::dispatch($province);

            return $province;
        });
        $findCountry = $this->prepare($findCountry);
        $findProvince = $this->prepare($findProvince);

        $country = $this->country($findCountry);

        if(is_numeric($findProvince) && $province = Province::find($findProvince)) {
            $province->setRelation('country', $country);
            return $province;
        }
        elseif($province = Province::where('country_id', $country->id)->where('slug', $findProvince)->first()) {
            $province->setRelation('country', $country);
            return $province;
        }

        $province = new Province;
        $province->country_id = $country->id;
        $province->title = $findProvince;
        $province->slug = $this->slug($findProvince, '-');
        $province->save();
        $province->setRelation('country', $country);

        GeocodeProvince::dispatch($province);
        
        return $province;
    }

    /**
     * Find or add the city
     */
    public function city($findCountry, $findProvince, $findCity, $lat = null, $lng = null)
    {
        $country = $this->country($findCountry);
        $province = $this->province($findCountry, $findProvince);

        return Cache::remember('city.'.$findCountry.'.'.$findProvince.'.'.$findCity, 60 * 60, function() use ($country, $province, $findCity, $lat, $lng) {
            if(is_numeric($findCity) && $city = City::find($findCity)) {
                $city->setRelation('country', $country);
                $city->setRelation('province', $province);
                return $city;
            }
            elseif($province && $city = City::where('country_id', $country->id)->where('province_id', $province->id)->where('slug', $this->slug($findCity))->first()) {
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
            ->whereRaw('SOUNDEX(title) = SOUNDEX(?)', [$findCity])->get(), $findCity)) {
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
}