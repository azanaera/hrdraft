<?php

namespace App\Domain\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature_type' => ['required', 'in:typed,checkbox'],
            'signature_data' => ['nullable', 'string', 'max:500'],
        ];
    }
}
