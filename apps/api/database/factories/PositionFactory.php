<?php

namespace Database\Factories;

use App\Domain\Employee\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

class PositionFactory extends Factory
{
    protected $model = \App\Domain\Employee\Models\Position::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->jobTitle(),
            'department_id' => Department::factory(),
            'default_employment_type' => $this->faker->randomElement(['hourly', 'salaried']),
            'is_active' => true,
        ];
    }
}
