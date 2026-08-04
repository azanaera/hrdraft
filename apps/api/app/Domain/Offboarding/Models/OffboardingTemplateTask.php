<?php

namespace App\Domain\Offboarding\Models;

use Illuminate\Database\Eloquent\Model;

class OffboardingTemplateTask extends Model
{
    protected $fillable = ['template_id', 'title', 'task_type', 'order', 'is_required'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function template()
    {
        return $this->belongsTo(OffboardingTemplate::class, 'template_id');
    }
}
