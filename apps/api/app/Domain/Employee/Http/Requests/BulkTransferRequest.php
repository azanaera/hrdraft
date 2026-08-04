<?php

namespace App\Domain\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasBackOfficeAccess();
    }

    public function rules(): array
    {
        return [
            'employment_ids' => ['required', 'array', 'min:1'],
            'employment_ids.*' => ['integer', 'exists:employments,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'position_id' => ['required', 'exists:positions,id'],
            'manager_employment_id' => ['nullable', 'exists:employments,id'],
            'effective_start_date' => ['required', 'date'],
        ];
    }
}
