<?php

namespace Thorazine\Geo\Console\Commands;

use App\Models\Tag;
use Illuminate\Console\Command;

class SyncTags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:tags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the tags with the new entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach(__('geo::tags') as $key => $category) {
            Tag::firstOrCreate([
                'title' => $key
            ]);
        }
    }
}
