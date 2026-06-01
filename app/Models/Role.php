<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'guard_name',
        'parent_id',
        'description',
        'role_type',
        'level',
        'abilities',
    ];

    protected $casts = [
        'abilities' => 'array',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function ancestors()
    {
        $ancestors = collect();
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            $ancestors->push($current);
        }

        return $ancestors;
    }

    public function descendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    public function getHierarchyAttribute()
    {
        return $this->ancestors()->reverse();
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithAncestors($query)
    {
        return $query->with('parent', 'ancestors');
    }
}
