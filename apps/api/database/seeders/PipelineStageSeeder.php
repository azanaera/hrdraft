<?php

namespace Database\Seeders;

use App\Domain\ATS\Models\PipelineStage;
use Illuminate\Database\Seeder;

class PipelineStageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['name' => 'Applied', 'order' => 1, 'is_terminal' => false],
            ['name' => 'Phone Screen', 'order' => 2, 'is_terminal' => false],
            ['name' => 'Interview', 'order' => 3, 'is_terminal' => false],
            ['name' => 'Offer', 'order' => 4, 'is_terminal' => false],
            ['name' => 'Hired', 'order' => 5, 'is_terminal' => true],
            ['name' => 'Rejected', 'order' => 6, 'is_terminal' => true],
        ] as $stage) {
            PipelineStage::firstOrCreate(['name' => $stage['name']], $stage);
        }
    }
}
