<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Owner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'ic',
        'mailing_address',
        'email',
        'owner_type',
        'latest_activity_id',
    ];

    protected $casts = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function phoneNumbers(): BelongsToMany
    {
        return $this->belongsToMany(PhoneNumber::class, 'owner_phone_number')
            ->using(OwnerPhoneNumber::class)
            ->withPivot('status')
            ->withTimestamps();
    }

    public function listings(): HasMany
    {
        return $this->hasMany(UnitListing::class);
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
