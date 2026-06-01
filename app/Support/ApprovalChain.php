<?php

namespace App\Support;

use App\Models\ApprovalRule;
use App\Models\Role;
use App\Models\User;

class ApprovalChain
{
    /**
     * Build sequential approval chain (user ids).
     * Uses dynamic rule (max levels) + manager hierarchy.
     * Admin must never be in approval chain.
     */
    public static function buildForChangeRequest(\App\Models\ChangeRequest $cr, User $requester): array
    {
        $maxLevels = ApprovalRule::resolveMaxLevelsForChangeRequest($cr);
        $chain = collect();

        $currentRole = static::primaryRole($requester);
        $guard = 0;
        while ($currentRole && $currentRole->parent && $guard < 20 && $chain->count() < $maxLevels) {
            $guard++;

            $parentRole = $currentRole->parent;
            $approver = static::findApproverByRole($parentRole, $requester->id);
            if ($approver) {
                $chain->push($approver->id);
            }

            $currentRole = $parentRole;
        }

        return $chain->unique()->values()->all();
    }

    protected static function primaryRole(User $user): ?Role
    {
        return $user->roles()->orderBy('level')->orderBy('id')->first();
    }

    protected static function findApproverByRole(Role $role, int $excludeUserId): ?User
    {
        return User::query()
            ->where('is_active', true)
            ->whereKeyNot($excludeUserId)
            ->whereHas('roles', function ($q) use ($role) {
                $q->where('roles.id', $role->id);
            })
            ->orderBy('id')
            ->get()
            ->first(function (User $u) {
                return $u->can('approve change_request');
            });
    }
}
