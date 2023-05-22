<?php

namespace Thorazine\Geo\Jobs;

use Thorazine\Geo\Models\Province;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class GeocodeProvince implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Province $province;

    /**
     * Create a new job instance.
     */
    public function __construct(Province $province)
    {
        $this->province = $province;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->province->geocode();
    }
}
