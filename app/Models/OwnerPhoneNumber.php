<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OwnerPhoneNumber extends Pivot
{
    public $incrementing = true;

    protected $table = 'owner_phone_number';

    protected $fillable = ['owner_id', 'phone_number_id', 'status'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }
}
