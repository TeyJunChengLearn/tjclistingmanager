<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PhoneNumber extends Model
{
    protected $fillable = ['phone_number', 'type'];

    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Owner::class, 'owner_phone_number')
            ->using(OwnerPhoneNumber::class)
            ->withPivot('status')
            ->withTimestamps();
    }
}
