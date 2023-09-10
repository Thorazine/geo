<?php

namespace Thorazine\Geo\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    protected $appends = [
        'tag'
    ];

    protected $fillable = [
        'title'
    ];

    public function getTagAttribute()
    {
        return __('tags.'.$this->title);
    }

    static public function key($key)
    {
        return self::where('title', $key)->first();
    }

    static public function ids($genres)
    {
        return self::select('id')
            ->whereIn('title', $genres)
            ->pluck('id')
            ->flatten()
            ->toArray() ?: [];
    }
}
