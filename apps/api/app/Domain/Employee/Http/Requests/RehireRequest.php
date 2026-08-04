<?php

namespace App\Domain\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RehireRequest extends FormRequest
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
        ];
    }
}
