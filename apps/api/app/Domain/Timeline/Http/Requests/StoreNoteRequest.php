<?php

namespace App\Domain\Timeline\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasBackOfficeAccess() || $this->user()->role === 'people_manager';
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:2000'],
            'visibility' => ['nullable', 'in:all_hr,manager_and_above,admin_only'],
            'event_date' => ['nullable', 'date'],
        ];
    }
}
