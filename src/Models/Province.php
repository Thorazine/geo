<?php

namespace Thorazine\Geo\Models;

use Illuminate\Support\Str;
use Thorazine\Geo\Models\City;
use Thorazine\Geo\Models\Country;
use Illuminate\Support\Facades\DB;
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

    protected $fillable = [
        'title',
        'country_id',
        'slug',
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

    public static function getOrGeo(Country $country, string|null $province)
    {
        if(! $province) return null;

        if($provinceModel = self::where('country_id', $country->id)
            ->levenshtein(Str::ascii($province), 1)
            ->levenshteinOrder(Str::ascii($province))
            ->first()) {
            return $provinceModel;
        }

        $geoProvince = (new Geolocate)->country($country->title)->province($province)->get();

        if(! $geoProvince->has()) {
            return null;
        }

        $provinceModel = new Province;
        $provinceModel->country_id = $country->id;
        $provinceModel->title = $geoProvince->province;
        $provinceModel->title_short = $geoProvince->provinceIso;
        $provinceModel->location = new Point($geoProvince->lat, $geoProvince->lng);
        $provinceModel->has_geo = true;
        $provinceModel->save();

        return $provinceModel;
    }

    public function scopeLevenshtein($query, $province, $limit = 1)
    {
        $province = Str::ascii($province);
        return $query->whereRaw('LEVENSHTEIN(search_title, ?) < '.$limit, [$province]);
    }

    public function scopeLevenshteinOrder($query, $province)
    {
        $province = Str::ascii($province);
        return $query->select(DB::raw('provinces.*, LEVENSHTEIN(search_title, "'.$province.'") as distance'))
            ->orderBy('distance', 'asc');
    }
}
