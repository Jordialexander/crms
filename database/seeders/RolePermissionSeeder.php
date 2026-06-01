<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'id'          => 1,
                'name'        => 'admin',
                'guard_name'  => 'web',
                'description' => 'Administrator dengan akses penuh',
                'role_type'   => null,
                'parent_id'   => null,
                'level'       => 1,
                'abilities'   => [
                    'view dashboard','view change_request','view own change_request','view team change_request',
                    'view all change_request','create change_request','edit change_request','delete change_request',
                    'submit change_request','cancel change_request','implement change_request','view approval',
                    'approve change_request','reject change_request','manage approval_rules','view schedule',
                    'create schedule','edit schedule','view implementation','create implementation','edit implementation',
                    'view risk_assessment','create risk_assessment','edit risk_assessment','view report','export report',
                    'view user','create user','edit user','delete user','manage roles','view activity_log',
                    'view need_review','view under_review','manage cr_options',
                ],
            ],
            [
                'id'          => 2,
                'name'        => 'manager',
                'guard_name'  => 'web',
                'description' => 'Manajer — reviewer dan approver tunggal',
                'role_type'   => 'approver',
                'parent_id'   => null,
                'level'       => 1,
                'abilities'   => [
                    'view dashboard','view change_request','view own change_request','view team change_request',
                    'view all change_request','view approval','approve change_request','reject change_request',
                    'view schedule','view implementation','view risk_assessment','view report','export report',
                    'view activity_log','view need_review','view under_review',
                ],
            ],
            [
                'id'          => 3,
                'name'        => 'lead_engineer',
                'guard_name'  => 'web',
                'description' => 'Lead Engineer — membuat dan submit CR, memilih engineer sebagai PIC',
                'role_type'   => 'requester',
                'parent_id'   => 2,
                'level'       => 2,
                'abilities'   => [
                    'view dashboard','view change_request','view own change_request',
                    'create change_request','edit change_request','submit change_request',
                    'cancel change_request','view risk_assessment','view schedule','view implementation',
                ],
            ],
            [
                'id'          => 4,
                'name'        => 'lead_developer',
                'guard_name'  => 'web',
                'description' => 'Lead Developer — membuat dan submit CR, memilih developer sebagai PIC',
                'role_type'   => 'requester',
                'parent_id'   => 2,
                'level'       => 2,
                'abilities'   => [
                    'view dashboard','view change_request','view own change_request',
                    'create change_request','edit change_request','submit change_request',
                    'cancel change_request','view risk_assessment','view schedule','view implementation',
                ],
            ],
            [
                'id'          => 5,
                'name'        => 'engineer',
                'guard_name'  => 'web',
                'description' => 'Engineer — membuat jadwal dan implementasi',
                'role_type'   => 'engineer',
                'parent_id'   => 3,
                'level'       => 3,
                'abilities'   => [
                    'view dashboard','view change_request','view own change_request','implement change_request',
                    'view risk_assessment','view schedule','create schedule','edit schedule',
                    'view implementation','create implementation','edit implementation',
                ],
            ],
            [
                'id'          => 6,
                'name'        => 'developer',
                'guard_name'  => 'web',
                'description' => 'Developer — membuat jadwal dan implementasi',
                'role_type'   => 'engineer',
                'parent_id'   => 4,
                'level'       => 3,
                'abilities'   => [
                    'view dashboard','view change_request','view own change_request','implement change_request',
                    'view risk_assessment','view schedule','create schedule','edit schedule',
                    'view implementation','create implementation','edit implementation',
                ],
            ],
        ];

        foreach ($roles as $data) {
            $abilities = $data['abilities'];
            unset($data['abilities']);

            $role = Role::withTrashed()->firstOrNew(['id' => $data['id']]);
            $role->fill($data);
            $role->save();

            // Use raw update to bypass any Spatie cast/guard on abilities
            \Illuminate\Support\Facades\DB::table('roles')
                ->where('id', $role->id)
                ->update(['abilities' => json_encode($abilities)]);
        }
    }
}
