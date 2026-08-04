<?php

namespace App\Domain\Offboarding\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OffboardingWorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employment_id' => $this->employment_id,
            'template' => $this->whenLoaded('template', fn () => $this->template->name),
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'tasks' => $this->whenLoaded('tasks', fn () => $this->tasks->map(fn ($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'task_type' => $t->task_type,
                'status' => $t->status,
                'completed_at' => $t->completed_at?->toIso8601String(),
            ])),
        ];
    }
}
