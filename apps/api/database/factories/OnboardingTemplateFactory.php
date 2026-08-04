<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OnboardingTemplateFactory extends Factory
{
    protected $model = \App\Domain\Onboarding\Models\OnboardingTemplate::class;

    public function definition(): array
    {
        return [
            'name' => 'Standard Onboarding',
            'is_active' => true,
        ];
    }
}
