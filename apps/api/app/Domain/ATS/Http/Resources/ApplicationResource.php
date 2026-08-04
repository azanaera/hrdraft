<?php

namespace App\Domain\ATS\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'requisition_id' => $this->requisition_id,
            'requisition_title' => $this->whenLoaded('requisition', fn () => $this->requisition->title),
            'candidate' => $this->whenLoaded('candidate', fn () => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->fullName(),
                'email' => $this->candidate->email,
                'is_former_employee' => $this->candidate->linked_person_id !== null,
                'possible_former_employee_id' => $this->candidate->possible_former_employee_person_id,
            ]),
            'current_stage' => $this->whenLoaded('currentStage', fn () => $this->currentStage->name),
            'status' => $this->status,
            'applied_at' => $this->applied_at?->toIso8601String(),
            'hired_employment_id' => $this->hired_employment_id,
        ];
    }
}
