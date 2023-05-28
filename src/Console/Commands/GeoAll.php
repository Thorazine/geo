<?php

namespace Thorazine\Geo\Console\Commands;

use Illuminate\Console\Command;

class GeoAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geo:all';

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
        //
    }
}
