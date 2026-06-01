<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrOption extends Model
{
    protected $fillable = ['type', 'value', 'label', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function ofType(string $type): \Illuminate\Support\Collection
    {
        return static::where('type', $type)
            ->where('is_active', true)
            ->orderBy('label')
            ->get();
    }

    public static function valuesForType(string $type): array
    {
        return static::ofType($type)->pluck('value')->all();
    }
}
