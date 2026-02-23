<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\App\Models\Level;

class DefaultLevelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates default system levels for academic centers
     */
    public function run(): void
    {
        $defaultLevels = [
            // Primary Stage
            ['name' => '1 Primary', 'code' => 'PRIM-1', 'stage' => 'primary', 'level_number' => 1],
            ['name' => '2 Primary', 'code' => 'PRIM-2', 'stage' => 'primary', 'level_number' => 2],
            ['name' => '3 Primary', 'code' => 'PRIM-3', 'stage' => 'primary', 'level_number' => 3],
            ['name' => '4 Primary', 'code' => 'PRIM-4', 'stage' => 'primary', 'level_number' => 4],
            ['name' => '5 Primary', 'code' => 'PRIM-5', 'stage' => 'primary', 'level_number' => 5],
            ['name' => '6 Primary', 'code' => 'PRIM-6', 'stage' => 'primary', 'level_number' => 6],
            
            // Preparatory Stage
            ['name' => '1 Preparatory', 'code' => 'PREP-1', 'stage' => 'preparatory', 'level_number' => 1],
            ['name' => '2 Preparatory', 'code' => 'PREP-2', 'stage' => 'preparatory', 'level_number' => 2],
            ['name' => '3 Preparatory', 'code' => 'PREP-3', 'stage' => 'preparatory', 'level_number' => 3],
            
            // Secondary Stage
            ['name' => '1 Secondary', 'code' => 'SEC-1', 'stage' => 'secondary', 'level_number' => 1],
            ['name' => '2 Secondary', 'code' => 'SEC-2', 'stage' => 'secondary', 'level_number' => 2],
            ['name' => '3 Secondary', 'code' => 'SEC-3', 'stage' => 'secondary', 'level_number' => 3],
        ];

        foreach ($defaultLevels as $levelData) {
            Level::updateOrCreate(
                [
                    'name' => $levelData['name'],
                    'channel_id' => null, // System default
                ],
                [
                    'code' => $levelData['code'],
                    'level_number' => $levelData['level_number'],
                    'stage' => $levelData['stage'],
                    'is_active' => true,
                    'is_default' => true,
                    'channel_id' => null,
                    'description' => "Default level for {$levelData['name']}",
                ]
            );
        }
    }
}

