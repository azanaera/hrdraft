<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentCategoryFactory extends Factory
{
    protected $model = \App\Domain\Documents\Models\DocumentCategory::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['I-9', 'W-4', 'Offer Letter', 'ID Verification', 'Handbook Acknowledgment']),
            'requires_signature' => false,
            'applicable_to' => 'all',
        ];
    }
}
