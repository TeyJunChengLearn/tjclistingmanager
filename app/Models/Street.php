<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Street extends Model
{
    protected $fillable = ['local_area_id', 'name'];

    public function localArea(): BelongsTo
    {
        return $this->belongsTo(LocalArea::class);
    }

    public function buildings(): HasMany
    {
        return $this->hasMany(Building::class);
    }
}
