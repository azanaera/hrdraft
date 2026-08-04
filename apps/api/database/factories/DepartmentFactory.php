<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DepartmentFactory extends Factory
{
    protected $model = \App\Domain\Employee\Models\Department::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Warehouse Ops', 'Retail Floor', 'Logistics', 'Customer Support', 'Corporate HR']).' '.$this->faker->randomNumber(3),
            'code' => strtoupper($this->faker->unique()->lexify('DEPT-???')),
            'is_active' => true,
        ];
    }
}
