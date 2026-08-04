<?php

namespace Database\Factories;

use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobRequisitionFactory extends Factory
{
    protected $model = \App\Domain\ATS\Models\JobRequisition::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->jobTitle(),
            'department_id' => Department::factory(),
            'location_id' => Location::factory(),
            'status' => 'open',
            'employment_type' => $this->faker->randomElement(['hourly', 'salaried']),
            'opened_at' => now(),
        ];
    }
}
