<?php

namespace App\Domain\Compensation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankingInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employment = $this->route('employment');

        return $this->user()->hasBackOfficeAccess() || $this->user()->employment_id === $employment->id;
    }

    public function rules(): array
    {
        return [
            'routing_number' => ['required', 'string', 'digits:9'],
            'account_number' => ['required', 'string', 'min:4', 'max:17'],
            'account_type' => ['required', 'in:checking,savings'],
        ];
    }
}
