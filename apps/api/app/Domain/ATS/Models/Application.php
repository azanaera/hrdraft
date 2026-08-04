<?php

namespace App\Domain\ATS\Models;

use App\Domain\Employee\Models\Employment;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = ['requisition_id', 'candidate_id', 'current_stage_id', 'status', 'applied_at', 'rejected_reason', 'hired_employment_id'];

    protected function casts(): array
    {
        return ['applied_at' => 'datetime'];
    }

    public function requisition()
    {
        return $this->belongsTo(JobRequisition::class, 'requisition_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function currentStage()
    {
        return $this->belongsTo(PipelineStage::class, 'current_stage_id');
    }

    public function hiredEmployment()
    {
        return $this->belongsTo(Employment::class, 'hired_employment_id');
    }

    public function stageHistory()
    {
        return $this->hasMany(ApplicationStageHistory::class);
    }

    public function interviewNotes()
    {
        return $this->hasMany(InterviewNote::class);
    }
}
