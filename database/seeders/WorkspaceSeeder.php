<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workspace;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        Workspace::firstOrCreate(
            ['slug' => 'demo'],
            [
                'name' => 'Demo Company',
                'default_timezone' => 'America/New_York',
                'case_label' => 'Ticket'
            ]
        );
    }
}
