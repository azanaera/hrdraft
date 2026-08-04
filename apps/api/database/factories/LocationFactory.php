<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class LocationFactory extends Factory
{
    protected $model = \App\Domain\Employee\Models\Location::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city().' Site',
            'code' => strtoupper($this->faker->unique()->lexify('LOC-???')),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'country' => 'US',
            'timezone' => 'America/New_York',
            'is_active' => true,
        ];
    }
}
