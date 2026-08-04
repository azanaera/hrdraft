<?php

namespace App\Domain\Timeline\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employment_id' => $this->employment_id,
            'event_type' => $this->event_type,
            'event_date' => $this->event_date?->toDateString(),
            'summary' => $this->summary,
            'payload' => $this->payload,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor?->name),
            'visibility' => $this->visibility,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
