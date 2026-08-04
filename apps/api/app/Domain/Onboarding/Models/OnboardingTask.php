<?php

namespace App\Domain\Onboarding\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OnboardingTask extends Model
{
    protected $fillable = [
        'workflow_id', 'template_task_id', 'title', 'task_type', 'status',
        'assigned_to_user_id', 'completed_at', 'completed_by_user_id', 'related_document_id',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function workflow()
    {
        return $this->belongsTo(OnboardingWorkflow::class, 'workflow_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
