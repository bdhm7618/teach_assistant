<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Channel\Database\Seeders\ChannelDatabaseSeeder;
use Modules\Student\Database\Seeders\StudentDatabaseSeeder;
use Modules\Academic\Database\Seeders\AcademicDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AcademicDatabaseSeeder::class,
            StudentDatabaseSeeder::class,
            ChannelDatabaseSeeder::class,
        ]);
    }
}
