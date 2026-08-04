<?php

namespace App\Domain\Notifications\Models;

use App\Domain\Employee\Models\Employment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = ['user_id', 'type', 'title', 'body', 'related_employment_id', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function relatedEmployment()
    {
        return $this->belongsTo(Employment::class, 'related_employment_id');
    }
}
