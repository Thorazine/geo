<?php

namespace Thorazine\Geo\Console\Commands;

use Illuminate\Support\Str;
use Thorazine\Geo\Models\City;
use Illuminate\Console\Command;
use Thorazine\Geo\Models\Country;
use Thorazine\Geo\Models\Province;
use MatanYadaev\EloquentSpatial\Objects\Point;

class ImportCities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import all cities from a csv file';

    protected $priorities = [
        'NL' => 990,
        'BE' => 980,
        'DE' => 970,
        'FR' => 960,
        'GB' => 950,
        'ES' => 940,
        'IT' => 930,
        'PT' => 920,
        'AT' => 910,
        'CH' => 900,
        'DK' => 890,
        'SE' => 880,
        'NO' => 870,
        'FI' => 860,
    ];

    protected $countries = [];
    protected $cities = [];
    protected $provinces = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // https://simplemaps.com/data/world-cities
        $filename = __DIR__.'/files/cities.csv';
        $file = fopen($filename, 'r');
        $fp = file($filename);
        $bar = $this->output->createProgressBar(count($fp));
        $bar->start();
        
        $keys = [];
        while (($line = fgetcsv($file)) !== FALSE) {
            $bar->advance();
            if(! count($keys)) {
                $keys = $line;
                continue;
            }
            else {
                $line = array_combine($keys, $line);
            }
            //$line is an array of the csv elements

            $countryId = $this->countryId($line);
            $provinceId = $this->provinceId($countryId, $line);

            // id,name,state_id,state_code,state_name,country_id,country_code,country_name,latitude,longitude,wikiDataId
            // 77340,Amsterdam,2612,NH,"North Holland",156,NL,Netherlands,52.37403000,4.88969000,Q727

            if(array_key_exists($line['name'].'|'.$countryId.'|'.$provinceId, $this->cities)) {
                continue;
            }

            if(@$city->is_user_altered) {
                continue;
            }


            if($city = City::where('title', $line['name'])
                ->where('country_id', $countryId)
                ->where('province_id', $provinceId)
                ->first()) {
                $this->cities[$line['name'].'|'.$countryId.'|'.$provinceId] = $city->id;
                continue;
            }

            $city = new City;
            $city->slug = Str::slug($line['name']);
            $city->title = $line['name'];
            $city->search_title = Str::ascii($line['name']);
            $city->country_id = $countryId;
            $city->province_id = $provinceId;
            $city->location = new Point($line['latitude'], $line['longitude']);
            $city->has_geo = true;
            $city->is_checked = true;
            $city->save();

            $this->cities[$line['name'].'|'.$countryId.'|'.$provinceId] = $city->id;
        }
        fclose($file);
        $bar->finish();
    }

    private function countryId($line)
    {
        if(array_key_exists($line['country_code'], $this->countries)) {
            return $this->countries[$line['country_code']];
        }

        if($country = Country::where('title', strtoupper($line['country_code']))->first()) {
            $this->countries[$line['country_code']] = $country->id;
            return $country->id;
        }
        
        $country = new Country;
        $country->title = strtoupper($line['country_code']);
        $country->priority = @$this->priorities[$country->title] ?: 0;
        $country->slug = Str::slug($line['country_code']);
        $country->is_checked = true;
        $country->save();

        $this->countries[$line['country_code']] = $country->id;

        return $country->id;

    }

    private function provinceId($countryId, $line)
    {
        if(! $line['state_name']) {
            return null;
        }

        if(array_key_exists($line['state_name'].'|'.$countryId, $this->provinces)) {
            return $this->provinces[$line['state_name'].'|'.$countryId];
        }

        if($province = Province::where('title', $line['state_name'])
            ->where('country_id', $countryId)
            ->first()) {
            $this->provinces[$line['state_name'].'|'.$countryId] = $province->id;
            return $province->id;
        }

        $province = new Province;
        $province->title = $line['state_name'];
        $province->slug = Str::slug($line['state_name']);
        $province->search_title = Str::ascii($line['state_name']);
        $province->title_short = $line['state_code'];
        $province->country_id = $countryId;
        $province->is_checked = true;
        $province->save();

        $this->provinces[$line['state_name'].'|'.$countryId] = $province->id;
        
        return $province->id;

    }
}
