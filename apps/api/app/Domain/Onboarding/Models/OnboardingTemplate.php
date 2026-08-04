<?php

namespace App\Domain\Onboarding\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'applicable_employment_type', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tasks()
    {
        return $this->hasMany(OnboardingTemplateTask::class, 'template_id')->orderBy('order');
    }
}
