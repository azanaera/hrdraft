<?php

namespace App\Domain\TimeOff\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideTimeOffRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TimeOffService::canDecide() enforces the real check in the controller
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
