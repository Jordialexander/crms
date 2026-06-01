<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ApprovalRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['id'=>1, 'name'=>'Administrator',  'username'=>'admin',        'email'=>'admin@sikece.test',        'role'=>'admin'],
            ['id'=>2, 'name'=>'Budi Santoso',   'username'=>'manager',      'email'=>'manager@sikece.test',      'role'=>'manager'],
            ['id'=>3, 'name'=>'Andi Pratama',   'username'=>'lead_engineer','email'=>'lead.engineer@sikece.test','role'=>'lead_engineer'],
            ['id'=>4, 'name'=>'Sari Dewi',      'username'=>'lead_developer','email'=>'lead.dev@sikece.test',    'role'=>'lead_developer'],
            ['id'=>5, 'name'=>'Rudi Hartono',   'username'=>'engineer1',    'email'=>'engineer1@sikece.test',    'role'=>'engineer'],
            ['id'=>6, 'name'=>'Tono Wijaya',    'username'=>'engineer2',    'email'=>'engineer2@sikece.test',    'role'=>'engineer'],
            ['id'=>7, 'name'=>'Dewi Kusuma',    'username'=>'developer1',   'email'=>'developer1@sikece.test',   'role'=>'developer'],
            ['id'=>8, 'name'=>'Hendra Gunawan', 'username'=>'developer2',   'email'=>'developer2@sikece.test',   'role'=>'developer'],
        ];

        foreach ($users as $data) {
            $roleName = $data['role'];
            unset($data['role']);

            $user = User::updateOrCreate(
                ['id' => $data['id']],
                array_merge($data, [
                    'password'     => Hash::make('password'),
                    'is_active'    => true,
                    'notify_email' => true,
                    'manager_id'   => null,
                ])
            );

            $user->syncRoles([$roleName]);
        }

        ApprovalRule::truncate();
        $rules = [
            ['priority'=>'low',      'change_type'=>null,'category'=>null,'max_levels'=>1,'name'=>'Low — 1 level (manager)',      'enabled'=>true],
            ['priority'=>'medium',   'change_type'=>null,'category'=>null,'max_levels'=>1,'name'=>'Medium — 1 level (manager)',   'enabled'=>true],
            ['priority'=>'high',     'change_type'=>null,'category'=>null,'max_levels'=>1,'name'=>'High — 1 level (manager)',     'enabled'=>true],
            ['priority'=>'critical', 'change_type'=>null,'category'=>null,'max_levels'=>1,'name'=>'Critical — 1 level (manager)','enabled'=>true],
        ];
        foreach ($rules as $rule) {
            ApprovalRule::create($rule);
        }
    }
}
