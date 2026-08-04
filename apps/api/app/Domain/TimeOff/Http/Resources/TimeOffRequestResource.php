<?php

namespace App\Domain\TimeOff\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimeOffRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employment_id' => $this->employment_id,
            'employee_name' => $this->whenLoaded('employment', fn () => $this->employment->person->fullName()),
            'policy' => $this->whenLoaded('policy', fn () => $this->policy->name),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'hours_requested' => (float) $this->hours_requested,
            'status' => $this->status,
            'requested_at' => $this->requested_at?->toIso8601String(),
            'decided_by' => $this->whenLoaded('decidedBy', fn () => $this->decidedBy?->name),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'decision_notes' => $this->decision_notes,
        ];
    }
}
