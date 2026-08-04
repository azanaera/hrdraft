<?php

namespace App\Domain\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TerminateEmploymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasBackOfficeAccess();
    }

    public function rules(): array
    {
        return [
            'termination_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
