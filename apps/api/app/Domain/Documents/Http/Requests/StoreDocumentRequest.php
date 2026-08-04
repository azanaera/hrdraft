<?php

namespace App\Domain\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // scoped in controller: back-office can upload for anyone, employee only for self
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:document_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:20480'],
        ];
    }
}
