<?php

namespace Database\Factories;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompensationRecordFactory extends Factory
{
    protected $model = \App\Domain\Compensation\Models\CompensationRecord::class;

    public function definition(): array
    {
        return [
            'employment_id' => Employment::factory(),
            'pay_type' => 'hourly',
            'rate_amount' => $this->faker->randomFloat(2, 16, 45),
            'pay_frequency' => 'biweekly',
            'currency' => 'USD',
            'effective_date' => now()->toDateString(),
            'reason' => 'new_hire',
        ];
    }
}
