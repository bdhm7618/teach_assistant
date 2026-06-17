<?php

namespace Modules\Channel\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $roles = [
            [
                'name'        => 'owner',
                'description' => 'Channel owner with full access',
                'permissions' => 'all',
            ],

            [
                'name'        => 'teacher',
                'description' => 'Teacher — manages groups, sessions, attendance, and students',
                'permissions' => [
                    'courses.view',
                    'groups.view',
                    'sessions.view', 'sessions.create', 'sessions.update',
                    'subjects.view',
                    'students.view',
                    'attendance.view', 'attendance.manage',
                    'reports.view',
                ],
            ],

            [
                'name'        => 'assistant',
                'description' => 'Assistant — limited operational access',
                'permissions' => [
                    'courses.view',
                    'groups.view',
                    'sessions.view',
                    'subjects.view',
                    'students.view', 'students.create',
                    'attendance.view', 'attendance.manage',
                ],
            ],

            [
                'name'        => 'viewer',
                'description' => 'Read-only access',
                'permissions' => [
                    'courses.view',
                    'groups.view',
                    'sessions.view',
                    'subjects.view',
                    'students.view',
                    'attendance.view',
                    'reports.view',
                ],
            ],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name'], 'channel_id' => null],
                [
                    'description' => $role['description'],
                    'permissions' => json_encode($role['permissions']),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]
            );
        }
    }
}
