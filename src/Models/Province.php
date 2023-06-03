<?php

namespace Thorazine\Geo\Models;

use Thorazine\Geo\Models\City;
use Thorazine\Geo\Models\Country;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Thorazine\Geo\Services\Maps\Geolocate;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Province extends Model
{
    use HasFactory, HasSpatial;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'location' => Point::class,
    ];

    protected $spatialFields = [
        'location',
    ];

    protected $fillable = [
        'title',
        'country_id',
        'slug',
        'external_ref_key'
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function geocode()
    {
        try {
            if(! $this->has_geo) {
                $geo = new Geolocate;
                $data = $geo->country($this->country->title)
                    ->province($this->title)
                    ->get();
                if($data->lat && $data->lng) {
                    $this->location = new Point($data->lat, $data->lng);
                    $this->has_geo = true;
                    $this->save();
                }
            }
        }
        catch(\Exception $e) {

        }
    }

    public function getTitleAttribute($value)
    {
        if(trans()->has('geo::geo.'.$value)) {
            return trans()->get('geo::geo.'.$value);
        }
        return $value;
    }

    public function getHashAttribute()
    {
        return Hashids::connection(strtolower((new \ReflectionClass($this))->getShortName()))->encode($this->id);
    }

    public static function bySlug(string $slug)
    {
        return self::where('slug', $slug)->firstOrFail();
    }
}
