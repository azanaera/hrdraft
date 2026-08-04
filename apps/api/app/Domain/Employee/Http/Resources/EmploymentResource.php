<?php

namespace App\Domain\Employee\Http\Resources;

use App\Domain\Compensation\Http\Resources\CompensationRecordResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmploymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_number' => $this->employee_number,
            'employment_status' => $this->employment_status,
            'employment_type' => $this->employment_type,
            'hire_date' => $this->hire_date?->toDateString(),
            'termination_date' => $this->termination_date?->toDateString(),
            'person' => [
                'id' => $this->person->id,
                'person_number' => $this->person->person_number,
                'first_name' => $this->person->first_name,
                'last_name' => $this->person->last_name,
                'personal_email' => $this->person->personal_email,
            ],
            'current_assignment' => $this->whenLoaded('currentAssignment', fn () => $this->currentAssignment ? [
                'id' => $this->currentAssignment->id,
                'department' => $this->currentAssignment->department?->name,
                'location' => $this->currentAssignment->location?->name,
                'position' => $this->currentAssignment->position?->title,
                'manager_employment_id' => $this->currentAssignment->manager_employment_id,
                'effective_start_date' => $this->currentAssignment->effective_start_date?->toDateString(),
            ] : null),
            'current_compensation' => CompensationRecordResource::make($this->whenLoaded('currentCompensation')),
        ];
    }
}
