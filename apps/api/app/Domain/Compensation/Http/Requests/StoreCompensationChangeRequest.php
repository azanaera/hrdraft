<?php

namespace App\Domain\Compensation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompensationChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasBackOfficeAccess();
    }

    public function rules(): array
    {
        return [
            'pay_type' => ['required', 'in:hourly,salary'],
            'rate_amount' => ['required', 'numeric', 'min:0'],
            'pay_frequency' => ['required', 'in:weekly,biweekly,semimonthly,monthly,annual'],
            'currency' => ['nullable', 'string', 'size:3'],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'in:new_hire,raise,promotion,transfer,rehire,adjustment,correction'],
            'related_assignment_id' => ['nullable', 'exists:assignments,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
