<?php

namespace Thorazine\Geo\Models;

use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coordinate extends Model
{
    use HasFactory, HasSpatial;

    protected $casts = [
        'location' => Point::class,
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public static function geo(float $latitude, float $longitude)
    {
        $point = new Point(round($latitude, 4).'5', round($longitude, 4).'5');

        if($coordinate = self::whereDistanceSphere('location', $point, '<', 10)
            ->orderByDistanceSphere('location', $point)
            ->first()) {
            return City::where('id', $coordinate->city_id)
                ->with('province', 'country')
                ->first();
        }
    }

    public static function new($cityId, $latitude, $longitude)
    {
        $coordinate = new self;
        $coordinate->city_id = $cityId;
        $coordinate->location = new Point(coordinateRound($latitude), coordinateRound($longitude));
        $coordinate->save();
    }
}