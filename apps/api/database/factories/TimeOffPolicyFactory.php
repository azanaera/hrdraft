<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TimeOffPolicyFactory extends Factory
{
    protected $model = \App\Domain\TimeOff\Models\TimeOffPolicy::class;

    public function definition(): array
    {
        return [
            'name' => 'PTO',
            'applies_to' => 'all',
            'accrual_method' => 'per_pay_period',
            'accrual_rate' => 3.0,
            'max_balance' => 120,
            'is_active' => true,
        ];
    }
}
