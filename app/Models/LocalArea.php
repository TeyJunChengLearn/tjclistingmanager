<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalArea extends Model
{
    protected $fillable = ['city_id', 'postal_code_id', 'name'];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function postalCode(): BelongsTo
    {
        return $this->belongsTo(PostalCode::class);
    }

    public function streets(): HasMany
    {
        return $this->hasMany(Street::class);
    }
}
