<?php

namespace App\Domain\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferEmploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transfer', $this->route('employment'));
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'exists:departments,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'manager_employment_id' => ['nullable', 'exists:employments,id'],
            'effective_start_date' => ['required', 'date'],
        ];
    }
}
