<?php

namespace App\Domain\TimeOff\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeOffRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // scoped to "own employment or back-office" in the controller
    }

    public function rules(): array
    {
        return [
            'employment_id' => ['required', 'exists:employments,id'],
            'policy_id' => ['required', 'exists:time_off_policies,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'hours_requested' => ['required', 'numeric', 'min:0.5'],
        ];
    }
}
