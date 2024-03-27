<?php

namespace Thorazine\Geo\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Thorazine\Geo\Services\Maps\Geolocate;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\FlareClient\Api;

class City extends Model
{
    use HasFactory, HasSpatial;

    protected $casts = [
        'location' => Point::class,
    ];

    protected $appends = [
        // 'hash',
    ];

    protected $hidden = [
        'id'
    ];

    protected $fillable = [
        'title',
        'slug',
        'province_id',
        'external_ref_key'
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function($model) {
            $model->slug = Str::slug($model->title);
            $model->search_title = Str::ascii($model->title);
        });
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function neighbourhoods()
    {
        return $this->hasMany(Neighbourhood::class);
    }

    public function geocode()
    {
        try {
            if(! $this->has_geo) {
                $geo = new Geolocate;
                $data = $geo->country($this->country->title)
                    ->province($this->province->title)
                    ->address($this->title)
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

    public function getHashAttribute()
    {
        return Hashids::connection(strtolower((new \ReflectionClass($this))->getShortName()))->encode($this->id);
    }

    public static function bySlug(string $slug)
    {
        return self::where('slug', $slug)->firstOrFail();
    }

    public static function getOrGeo(Country $country, Province|null $province, string $city)
    {
        // look in the DB
        if($cityModel = self::where('country_id', $country->id)
            ->when($province, function($query) use ($province) {
                return $query->where('province_id', $province->id);
            })
            ->levenshtein(Str::ascii($city), 1)
            ->levenshteinOrder(Str::ascii($city))
            ->with('province')
            ->first()) {
            $cityModel->setRelation('country', $country);

            if(! $province && ! $cityModel->province) {
                $geoCity = (new Geolocate)->country($country->title)->address($city)->get();

                if($geoCity->province) {
                    $province = Province::getOrGeo($country, $geoCity->province);
                }
            }

            return $cityModel;
        }

        // geo locate
        $geoCity = (new Geolocate)->country($country->title);
        if($province) $geoCity = $geoCity->province($province->title);
        $geoCity = $geoCity->address($city)->get();
        if(! $geoCity->has()) return null;

        // check if the province is in the DB or create it
        if(@$province->title || $geoCity->province) {
            $province = Province::getOrGeo($country, $province->title ?? $geoCity->province);
        }

        // add it to the DB
        $cityModel = new City;
        $cityModel->country_id = $country->id;
        $cityModel->province_id = $province->id;
        $cityModel->title = $geoCity->city;
        $cityModel->location = new Point(coordinateRound($geoCity->lat), coordinateRound($geoCity->lng));
        $cityModel->has_geo = true;
        $cityModel->save();

        $cityModel->setRelation('country', $country);
        $cityModel->setRelation('province', $province);

        return $cityModel;
    }

    public static function findByCoordinates($latitude, $longitude)
    {
        $latitude = coordinateRound($latitude);
        $longitude = coordinateRound($longitude);

        $point = new Point($latitude, $longitude);

        if($city = Coordinate::geo($latitude, $longitude)) {
            ApiCall::geoDb();
            return $city;
        }

        // find it with 100 m radius
        if($city = self::whereDistanceSphere('location', $point, '<', 100)
            ->orderByDistanceSphere('location', $point)
            ->with('province', 'country')
            ->first()) {
                Coordinate::new($city->id, $latitude, $longitude);
                return $city;
            }

        $geoCity = (new Geolocate)->coordinates($latitude, $longitude)->get();

        if(!$geoCity) return null;

        $country = Country::iso($geoCity->countryIso);
        $province = Province::getOrGeo($country, $geoCity->province);

        // find it in DB again
        if($cityModel = self::where('country_id', $country->id)
            ->where('province_id', $province->id)
            ->where('title', $geoCity->city)
            ->first()) {
            Coordinate::new($cityModel->id, $latitude, $longitude);
            $cityModel->setRelation('country', $country);
            $cityModel->setRelation('province', $province);
            return $cityModel;
        }

        $cityModel = new City;
        $cityModel->country_id = $country->id;
        if($province) {
            $cityModel->province_id = $province->id;
        }
        $cityModel->title = $geoCity->city;
        $cityModel->location = new Point(coordinateRound($geoCity->lat), coordinateRound($geoCity->lng));
        $cityModel->has_geo = true;
        $cityModel->save();

        Coordinate::new($cityModel->id, $latitude, $longitude);

        $cityModel->setRelation('country', $country);
        $cityModel->setRelation('province', $province);

        return $cityModel;
    }

    public function scopeLevenshtein($query, string $city, int $limit = 1)
    {
        $city = Str::ascii($city);
        return $query->whereRaw('LEVENSHTEIN(search_title, ?) < '.$limit, [$city]);
    }

    public function scopeLevenshteinOrder($query, string $city)
    {
        $city = Str::ascii($city);
        return $query->select(DB::raw('cities.*, LEVENSHTEIN(search_title, "'.$city.'") as distance'))
            ->orderBy('distance', 'asc');
    }
}
