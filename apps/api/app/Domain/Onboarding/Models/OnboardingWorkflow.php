<?php

namespace App\Domain\Onboarding\Models;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Model;

class OnboardingWorkflow extends Model
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
        return $this->belongsTo(OnboardingTemplate::class, 'template_id');
    }

    public function tasks()
    {
        return $this->hasMany(OnboardingTask::class, 'workflow_id');
    }
}
