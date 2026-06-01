<?php

namespace App\Http\Controllers;

use App\Models\ChangeRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected array $data = [];

    /**
     * Apply CR visibility scope based on the user's permissions.
     *  - view all change_request  → no restriction (all CR)
     *  - view team change_request → CR whose requester shares the same role subtree, or where user is PIC
     *  - otherwise                → only own CR or assigned as PIC
     */
    protected function applyVisibilityScope(Builder $query, User $user): Builder
    {
        if ($user->can('view all change_request')) {
            return $query;
        }

        if ($user->can('view team change_request')) {
            $teamRoleIds = $this->roleTreeIdsForUser($user);

            if (empty($teamRoleIds)) {
                return $query->where(fn($q) => $q
                    ->where('requester_id', $user->id)
                    ->orWhere('pic_id', $user->id)
                );
            }

            return $query->where(fn($q) => $q
                ->whereHas('requester.roles', fn($q2) => $q2->whereIn('roles.id', $teamRoleIds))
                ->orWhere('pic_id', $user->id)
            );
        }

        // Default: own CR + assigned as PIC
        return $query->where(fn($q) => $q
            ->where('requester_id', $user->id)
            ->orWhere('pic_id', $user->id)
        );
    }

    protected function roleTreeIdsForUser(User $user): array
    {
        $primaryRole = $user->roles()->orderBy('level')->orderBy('id')->first();
        if (!$primaryRole) {
            return [];
        }

        $descendants = $primaryRole->descendants()->pluck('id')->all();

        return collect([$primaryRole->id])
            ->merge($descendants)
            ->unique()
            ->values()
            ->all();
    }
}
