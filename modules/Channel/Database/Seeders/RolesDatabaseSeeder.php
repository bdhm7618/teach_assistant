<?php

namespace Modules\Channel\Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RolesDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $roles = [
            [
                'name' => 'owner',
                'description' => 'Channel owner with full system access',
                'permissions' => "all"
            ],

            [
                'name' => 'teacher',
                'description' => 'Teacher with access to students and lessons',
                'permissions' => [
                    'students.view',
                    'students.create',
                    'students.update',

                    'lessons.view',
                    'lessons.create',
                    'lessons.update',

                    'attendance.view',
                    'attendance.manage',

                    'reports.view',
                ],
            ],

            [
                'name' => 'assistant',
                'description' => 'Assistant with limited management permissions',
                'permissions' => [
                    'students.view',
                    'students.create',

                    'lessons.view',

                    'attendance.view',
                ],
            ],

            [
                'name' => 'viewer',
                'description' => 'Read-only access',
                'permissions' => [
                    'students.view',
                    'lessons.view',
                    'reports.view',
                ],
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']],
                [
                    'description' => $role['description'],
                    'permissions' => json_encode($role['permissions']),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
