<?php

namespace App\Domain\Compensation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankingInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'provider' => $this->provider,
            'account_last_four' => $this->account_last_four,
            'account_type' => $this->account_type,
            'verified' => $this->verified_at !== null,
        ];
    }
}
