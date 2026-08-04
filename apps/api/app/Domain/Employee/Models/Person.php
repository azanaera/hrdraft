<?php

namespace App\Domain\Employee\Models;

use App\Domain\Timeline\Models\EmployeeEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_number', 'first_name', 'last_name',
        'date_of_birth', 'personal_email', 'phone', 'ssn_encrypted',
    ];

    protected $hidden = ['ssn_encrypted'];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'ssn_encrypted' => 'encrypted',
        ];
    }

    public function employments()
    {
        return $this->hasMany(Employment::class)->orderByDesc('hire_date');
    }

    public function currentEmployment()
    {
        return $this->hasOne(Employment::class)->where('employment_status', '!=', 'terminated')->latestOfMany('hire_date');
    }

    public function events()
    {
        return $this->hasMany(EmployeeEvent::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
