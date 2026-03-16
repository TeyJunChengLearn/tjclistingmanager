<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PostalCode extends Model
{
    protected $fillable = ['city_id', 'code'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function localAreas(): HasMany
    {
        return $this->hasMany(LocalArea::class);
    }
}
