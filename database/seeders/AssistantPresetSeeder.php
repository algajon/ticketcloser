<?php

namespace Database\Seeders;

use App\Models\AssistantPreset;
use Illuminate\Database\Seeder;

class AssistantPresetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AssistantPreset::ensureDefaults();
    }
}
