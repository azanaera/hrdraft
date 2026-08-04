<?php

namespace App\Domain\Onboarding\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingTemplateTask extends Model
{
    protected $fillable = ['template_id', 'title', 'task_type', 'order', 'is_required', 'required_document_category_id'];

    protected function casts(): array
    {
        return ['is_required' => 'boolean'];
    }

    public function template()
    {
        return $this->belongsTo(OnboardingTemplate::class, 'template_id');
    }
}
