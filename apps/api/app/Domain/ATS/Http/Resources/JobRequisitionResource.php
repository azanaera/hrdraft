<?php

namespace App\Domain\ATS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobRequisitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'department' => $this->whenLoaded('department', fn () => $this->department->name),
            'location' => $this->whenLoaded('location', fn () => $this->location->name),
            'status' => $this->status,
            'employment_type' => $this->employment_type,
            'target_pay_range_min' => $this->target_pay_range_min !== null ? (float) $this->target_pay_range_min : null,
            'target_pay_range_max' => $this->target_pay_range_max !== null ? (float) $this->target_pay_range_max : null,
            'hiring_manager' => $this->whenLoaded('hiringManager', fn () => $this->hiringManager?->name),
            'applications_count' => $this->whenCounted('applications'),
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
        ];
    }
}
