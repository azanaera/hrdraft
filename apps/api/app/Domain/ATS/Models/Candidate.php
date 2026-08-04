<?php

namespace App\Domain\ATS\Models;

use App\Domain\Employee\Models\Person;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'resume_document_path', 'source',
        'linked_person_id', 'possible_former_employee_person_id',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function linkedPerson()
    {
        return $this->belongsTo(Person::class, 'linked_person_id');
    }

    public function possibleFormerEmployee()
    {
        return $this->belongsTo(Person::class, 'possible_former_employee_person_id');
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function fullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}
