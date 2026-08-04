<?php

namespace App\Domain\ATS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InterviewNote extends Model
{
    protected $fillable = ['application_id', 'interviewer_user_id', 'scheduled_at', 'notes', 'rating'];

    protected function casts(): array
    {
        return ['scheduled_at' => 'datetime'];
    }

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function interviewer()
    {
        return $this->belongsTo(User::class, 'interviewer_user_id');
    }
}
