<?php

namespace App\Domain\Compensation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompensationRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'id' => $this->id,
            'pay_type' => $this->pay_type,
            'rate_amount' => (float) $this->rate_amount,
            'pay_frequency' => $this->pay_frequency,
            'currency' => $this->currency,
            'effective_date' => $this->effective_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'reason' => $this->reason,
            'notes' => $this->notes,
        ];
    }
}
