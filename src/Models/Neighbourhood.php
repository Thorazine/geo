<?php

namespace Thorazine\Geo\Models;

use Illuminate\Support\Str;
use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use Thorazine\Geo\Services\Maps\Geolocate;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Neighbourhood extends Model
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
        'city_id',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function($model) {
            $model->slug = Str::slug($model->title);
            $model->search_title = Str::ascii($model->title);
        });
    }

    public function city()
    {
        return $this->belongsTo(City::class);
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
}
