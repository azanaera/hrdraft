<?php

namespace App\Domain\ATS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ApplicationStageHistory extends Model
{
    // Eloquent's default pluralization would guess "application_stage_histories" —
    // the actual migrated table name is singular "history", not "histories".
    protected $table = 'application_stage_history';

    protected $fillable = ['application_id', 'stage_id', 'entered_at', 'exited_at', 'moved_by_user_id'];

    protected function casts(): array
    {
        return [
            'entered_at' => 'datetime',
            'exited_at' => 'datetime',
        ];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'stage_id');
    }

    public function movedBy()
    {
        return $this->belongsTo(User::class, 'moved_by_user_id');
    }
}
