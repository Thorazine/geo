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
        if($cityModel = self::where('country_id', $country->id)
            ->when($province, function($query) use ($province) {
                return $query->where('province_id', $province->id);
            })
            ->levenshtein(Str::ascii($city), 1)
            ->levenshteinOrder(Str::ascii($city))
            ->first()) {
            $cityModel->setRelation('country', $country);

            if(! $province) {
                $geoCity = (new Geolocate)->country($country->title)->address($city)->get();

                if($geoCity->province) {
                    $province = Province::getOrGeo($country, $geoCity->province);
                }
            }

            $cityModel->setRelation('province', $province);
            return $cityModel;
        }

        $geoCity = (new Geolocate)->country($country->title);
        if($province) {
            $geoCity = $geoCity->province($province->title);
        }
        $geoCity = $geoCity->address($city)->get();

        if(! $geoCity->has()) {
            return null;
        }

        if(@$province->title || $geoCity->province) {
            $province = Province::getOrGeo($country, $province->title ?? $geoCity->province);
        }

        $cityModel = new City;
        $cityModel->country_id = $country->id;
        if($province) {
            $cityModel->province_id = $province->id;
        }
        $cityModel->title = $geoCity->city;
        $cityModel->location = new Point($geoCity->lat, $geoCity->lng);
        $cityModel->has_geo = true;
        $cityModel->save();

        $cityModel->setRelation('country', $country);
        $cityModel->setRelation('province', $province);

        return $cityModel;
    }

    public static function findByCoordinates($latitude, $longitude)
    {
        $point = new Point($latitude, $longitude);

        $city = self::whereDistanceSphere('location', $point, '<', 1000)
            ->orderByDistanceSphere('location', $point)
            ->with('province', 'country')
            ->first();

        if ($city) {
            return $city;
        }

        $geoCity = (new Geolocate)->coordinates($latitude, $longitude)->get();

        if(!$geoCity) {
            return null;
        }

        $country = Country::iso($geoCity->countryIso);
        $province = Province::getOrGeo($country, $geoCity->province);

        $cityModel = new City;
        $cityModel->country_id = $country->id;
        if($province) {
            $cityModel->province_id = $province->id;
        }
        $cityModel->title = $geoCity->city;
        $cityModel->location = new Point($geoCity->lat, $geoCity->lng);
        $cityModel->has_geo = true;
        $cityModel->save();

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
