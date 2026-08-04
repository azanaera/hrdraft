<?php

namespace App\Domain\Offboarding\Models;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Model;

class OffboardingWorkflow extends Model
{
    protected $fillable = ['employment_id', 'template_id', 'status', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }

    public function template()
    {
        return $this->belongsTo(OffboardingTemplate::class, 'template_id');
    }

    public function tasks()
    {
        return $this->hasMany(OffboardingTask::class, 'workflow_id');
    }
}
