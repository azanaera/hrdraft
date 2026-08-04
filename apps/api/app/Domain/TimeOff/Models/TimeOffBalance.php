<?php

namespace App\Domain\TimeOff\Models;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Model;

class TimeOffBalance extends Model
{
    protected $fillable = ['employment_id', 'policy_id', 'balance_hours', 'as_of_date'];

    protected function casts(): array
    {
        return [
            'balance_hours' => 'decimal:2',
            'as_of_date' => 'date',
        ];
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }

    public function policy()
    {
        return $this->belongsTo(TimeOffPolicy::class, 'policy_id');
    }
}
