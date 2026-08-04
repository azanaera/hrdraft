<?php

namespace App\Domain\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Domain\Employee\Models\Employment::class);
    }

    public function rules(): array
    {
        return [
            // Either link to an existing person (rehire) or create a new one.
            'person_id' => ['nullable', 'exists:people,id'],
            'first_name' => ['required_without:person_id', 'string', 'max:255'],
            'last_name' => ['required_without:person_id', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'personal_email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],

            'employee_number' => ['required', 'string', 'max:50', 'unique:employments,employee_number'],
            'hire_date' => ['required', 'date'],
            'employment_type' => ['required', 'in:hourly,salaried'],

            'department_id' => ['required', 'exists:departments,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'manager_employment_id' => ['nullable', 'exists:employments,id'],

            'pay_type' => ['required', 'in:hourly,salary'],
            'rate_amount' => ['required', 'numeric', 'min:0'],
            'pay_frequency' => ['required', 'in:weekly,biweekly,semimonthly,monthly,annual'],
        ];
    }
}
