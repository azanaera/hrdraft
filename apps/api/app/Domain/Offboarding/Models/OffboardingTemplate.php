<?php

namespace App\Domain\Offboarding\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OffboardingTemplate extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function tasks()
    {
        return $this->hasMany(OffboardingTemplateTask::class, 'template_id')->orderBy('order');
    }
}
