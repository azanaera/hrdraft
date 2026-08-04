<?php

namespace App\Domain\TimeOff\Models;

use App\Domain\Employee\Models\Employment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class TimeOffRequest extends Model
{
    protected $fillable = [
        'employment_id', 'policy_id', 'start_date', 'end_date', 'hours_requested',
        'status', 'requested_at', 'decided_by_user_id', 'decided_at', 'decision_notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'hours_requested' => 'decimal:2',
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
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

    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }
}
