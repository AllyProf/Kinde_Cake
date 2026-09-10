<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageUnit extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'description',
        'is_builtin',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_builtin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
