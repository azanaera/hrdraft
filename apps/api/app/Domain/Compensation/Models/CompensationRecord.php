<?php

namespace App\Domain\Compensation\Models;

use App\Domain\Employee\Models\Assignment;
use App\Domain\Employee\Models\Employment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompensationRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employment_id', 'pay_type', 'rate_amount', 'pay_frequency', 'currency',
        'effective_date', 'end_date', 'reason', 'related_assignment_id',
        'approved_by_user_id', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'rate_amount' => 'decimal:2',
            'effective_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }

    public function relatedAssignment()
    {
        return $this->belongsTo(Assignment::class, 'related_assignment_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
