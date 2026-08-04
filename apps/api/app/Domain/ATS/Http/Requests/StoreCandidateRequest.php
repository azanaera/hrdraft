<?php

namespace App\Domain\ATS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasBackOfficeAccess();
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:100'],
            'requisition_id' => ['required', 'exists:job_requisitions,id'],
        ];
    }
}
