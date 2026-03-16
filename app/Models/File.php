<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'size' => 'integer',
    ];
}
