<?php

namespace App\Domain\Employee\Models;

use App\Domain\Compensation\Models\CompensationRecord;
use App\Domain\Timeline\Models\EmployeeEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employment extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id', 'employee_number', 'hire_date', 'termination_date',
        'termination_reason', 'rehire_eligible', 'employment_status', 'employment_type',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'termination_date' => 'date',
            'rehire_eligible' => 'boolean',
        ];
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class)->orderByDesc('effective_start_date');
    }

    public function currentAssignment()
    {
        return $this->hasOne(Assignment::class)->where('is_current', true);
    }

    public function compensationRecords()
    {
        return $this->hasMany(CompensationRecord::class)->orderByDesc('effective_date');
    }

    public function currentCompensation()
    {
        return $this->hasOne(CompensationRecord::class)->whereNull('end_date');
    }

    public function events()
    {
        return $this->hasMany(EmployeeEvent::class);
    }

    public function isActive(): bool
    {
        return $this->employment_status !== 'terminated';
    }
}
