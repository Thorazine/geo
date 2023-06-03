<?php

namespace Thorazine\Geo\Models;

use Vinkla\Hashids\Facades\Hashids;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Country extends Model
{
    use HasFactory;

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
        'slug',
        'external_ref_key'
    ];

    protected $appends = [
        'name',
    ];

    public function provinces()
    {
        return $this->hasMany(Province::class);
    }

    public function getNameAttribute()
    {
        return __('geo::countries.'.$this->title);
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
