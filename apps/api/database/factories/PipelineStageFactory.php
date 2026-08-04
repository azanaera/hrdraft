<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineStageFactory extends Factory
{
    protected $model = \App\Domain\ATS\Models\PipelineStage::class;

    public function definition(): array
    {
        return [
            'name' => 'Applied',
            'order' => 1,
            'is_terminal' => false,
        ];
    }
}
