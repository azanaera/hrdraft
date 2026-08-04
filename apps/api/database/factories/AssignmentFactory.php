<?php

namespace Database\Factories;

use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Location;
use App\Domain\Employee\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssignmentFactory extends Factory
{
    protected $model = \App\Domain\Employee\Models\Assignment::class;

    public function definition(): array
    {
        return [
            'employment_id' => Employment::factory(),
            'department_id' => Department::factory(),
            'location_id' => Location::factory(),
            'position_id' => Position::factory(),
            'effective_start_date' => now()->toDateString(),
            'is_current' => true,
        ];
    }
}
