<?php

namespace App\Domain\TimeOff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeOffPolicy extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'applies_to', 'accrual_method', 'accrual_rate', 'max_balance', 'carryover_rule', 'is_active'];

    protected function casts(): array
    {
        return [
            'accrual_rate' => 'decimal:2',
            'max_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
