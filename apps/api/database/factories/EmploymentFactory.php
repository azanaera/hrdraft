<?php

namespace Database\Factories;

use App\Domain\Employee\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmploymentFactory extends Factory
{
    protected $model = \App\Domain\Employee\Models\Employment::class;

    public function definition(): array
    {
        return [
            'person_id' => Person::factory(),
            'employee_number' => 'E-'.strtoupper(Str::random(6)),
            'hire_date' => $this->faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'employment_status' => 'active',
            'employment_type' => $this->faker->randomElement(['hourly', 'salaried']),
            'rehire_eligible' => true,
        ];
    }

    public function terminated(): static
    {
        return $this->state(fn () => [
            'employment_status' => 'terminated',
            'termination_date' => now()->subMonth()->toDateString(),
            'termination_reason' => 'Voluntary resignation',
        ]);
    }
}
