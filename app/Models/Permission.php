<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'category',
    ];

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }
}
