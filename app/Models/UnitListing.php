<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnitListing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'unit_id',
        'owner_id',
        'team_id',
        'rental_price',
        'sale_price',
        'is_rent_available',
        'is_sale_available',
        'call_after',
        'status_filters',
        'latest_activity_id',
    ];

    protected $casts = [
        'rental_price'       => 'decimal:2',
        'sale_price'         => 'decimal:2',
        'is_rent_available'  => 'boolean',
        'is_sale_available'  => 'boolean',
        'call_after'         => 'date',
        'status_filters'     => 'array',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function latestActivity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'latest_activity_id');
    }

    public function files(): MorphToMany
    {
        return $this->morphToMany(File::class, 'fileable', 'fileables', 'fileable_id', 'file_id')
            ->using(Fileable::class)
            ->withPivot('collection', 'sort_order')
            ->orderByPivot('sort_order');
    }
}
