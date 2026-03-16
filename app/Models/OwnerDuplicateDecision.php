<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerDuplicateDecision extends Model
{
    protected $fillable = ['owner_id_1', 'owner_id_2'];

    public function owner1(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id_1');
    }

    public function owner2(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id_2');
    }
}
