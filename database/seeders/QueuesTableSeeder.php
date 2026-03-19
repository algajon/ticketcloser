<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QueuesTableSeeder extends Seeder
{
    public function run(): void
    {
        $workspaces = DB::table('workspaces')->pluck('id');
        foreach ($workspaces as $wsId) {
            DB::table('queues')->insertOrIgnore([
                [
                    'workspace_id' => $wsId,
                    'name' => 'maintenance',
                    'is_active' => true,
                    'default_priority' => 'high',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'workspace_id' => $wsId,
                    'name' => 'mortgage',
                    'is_active' => true,
                    'default_priority' => 'normal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'workspace_id' => $wsId,
                    'name' => 'support',
                    'is_active' => true,
                    'default_priority' => 'normal',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
