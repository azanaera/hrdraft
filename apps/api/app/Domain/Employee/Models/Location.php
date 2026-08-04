<?php

namespace App\Domain\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'address_line1', 'address_line2',
        'city', 'state', 'postal_code', 'country', 'timezone', 'minimum_wage', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'minimum_wage' => 'decimal:2',
        ];
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }
}
