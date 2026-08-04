<?php

namespace App\Domain\Timeline\Models;

use App\Domain\Employee\Models\Employment;
use App\Domain\Employee\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeEvent extends Model
{
    protected $fillable = [
        'person_id', 'employment_id', 'event_type', 'event_date',
        'summary', 'payload', 'actor_user_id', 'visibility',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'payload' => 'array',
        ];
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function employment()
    {
        return $this->belongsTo(Employment::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
