<?php

namespace Thorazine\Geo\Models;

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
        return __('countries.'.$this->title);
    }
}
