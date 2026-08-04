<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OffboardingTemplateFactory extends Factory
{
    protected $model = \App\Domain\Offboarding\Models\OffboardingTemplate::class;

    public function definition(): array
    {
        return [
            'name' => 'Standard Offboarding',
            'is_active' => true,
        ];
    }
}
