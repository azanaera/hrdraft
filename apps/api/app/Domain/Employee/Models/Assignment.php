<?php

namespace App\Domain\Employee\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employment_id', 'department_id', 'location_id', 'position_id',
        'manager_employment_id', 'effective_start_date', 'effective_end_date',
        'is_current', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function manager()
    {
        return $this->belongsTo(Employment::class, 'manager_employment_id');
    }
}
