<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class Fileable extends MorphPivot
{
    public $incrementing = true;

    protected $table = 'fileables';

    protected $fillable = ['file_id', 'fileable_type', 'fileable_id', 'collection', 'sort_order'];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
