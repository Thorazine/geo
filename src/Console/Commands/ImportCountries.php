<?php

namespace Thorazine\Geo\Console\Commands;

use Illuminate\Console\Command;
use Thorazine\Geo\Models\Country;
use Thorazine\Geo\Enums\Country as CountryEnum;


class ImportCountries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:countries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a geo location for all the countries, provinces and cities';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach(CountryEnum::cases() as $country) {
            $country = Country::firstOrCreate([
                'title' => $country->name,
            ]);
        }
    }
}
