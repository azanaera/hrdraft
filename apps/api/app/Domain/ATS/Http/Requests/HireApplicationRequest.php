<?php

namespace App\Domain\ATS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HireApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasBackOfficeAccess();
    }

    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:50', 'unique:employments,employee_number'],
            'hire_date' => ['required', 'date'],
            'employment_type' => ['required', 'in:hourly,salaried'],
            'position_id' => ['nullable', 'exists:positions,id'],
            'manager_employment_id' => ['nullable', 'exists:employments,id'],
            'pay_type' => ['required', 'in:hourly,salary'],
            'rate_amount' => ['required', 'numeric', 'min:0'],
            'pay_frequency' => ['required', 'in:weekly,biweekly,semimonthly,monthly,annual'],
        ];
    }
}
