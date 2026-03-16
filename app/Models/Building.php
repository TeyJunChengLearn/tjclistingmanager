<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    protected $fillable = ['street_id', 'name'];

    public function street(): BelongsTo
    {
        return $this->belongsTo(Street::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
