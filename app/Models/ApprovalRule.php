<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    protected $fillable = [
        'name',
        'change_type',
        'category',
        'priority',
        'max_levels',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'max_levels' => 'integer',
    ];

    /**
     * Resolve max approval levels for a CR based on enabled rules.
     * Match priority required; then prefer most specific:
     * 1) priority + change_type + category
     * 2) priority + change_type
     * 3) priority + category
     * 4) priority only
     */
    public static function resolveMaxLevelsForChangeRequest(ChangeRequest $cr): int
    {
        $priority = (string) $cr->priority;
        $changeType = (string) $cr->change_type;
        $category = (string) $cr->category;

        $base = static::query()->where('enabled', true)->where('priority', $priority);

        $candidates = [
            (clone $base)->where('change_type', $changeType)->where('category', $category),
            (clone $base)->where('change_type', $changeType)->whereNull('category'),
            (clone $base)->whereNull('change_type')->where('category', $category),
            (clone $base)->whereNull('change_type')->whereNull('category'),
        ];

        foreach ($candidates as $q) {
            $rule = $q->orderByDesc('max_levels')->orderBy('id')->first();
            if ($rule) {
                return max(1, (int) $rule->max_levels);
            }
        }

        return 1;
    }
}

