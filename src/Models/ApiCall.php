<?php

namespace Thorazine\Geo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'year_month',
        'count',
    ];

    public static function geo()
    {
        $apiCall = self::updateOrCreate([
            'type' => 'geo',
            'year_month' => date('Y-m-d'),
        ]);
        $apiCall->count++;
        $apiCall->save();
    }

    public static function geoDb()
    {
        $apiCall = self::updateOrCreate([
            'type' => 'geo_db',
            'year_month' => date('Y-m-d'),
        ]);
        $apiCall->count++;
        $apiCall->save();
    }

}