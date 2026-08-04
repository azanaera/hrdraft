<?php

namespace App\Domain\ATS\Models;

use App\Domain\Employee\Models\Department;
use App\Domain\Employee\Models\Location;
use App\Domain\Employee\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobRequisition extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'department_id', 'location_id', 'position_id', 'status',
        'hiring_manager_user_id', 'target_pay_range_min', 'target_pay_range_max',
        'employment_type', 'opened_at', 'closed_at', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'target_pay_range_min' => 'decimal:2',
            'target_pay_range_max' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
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

    public function hiringManager()
    {
        return $this->belongsTo(User::class, 'hiring_manager_user_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'requisition_id');
    }
}
