<?php

namespace App\Domain\ATS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequisitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasBackOfficeAccess();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'location_id' => ['required', 'exists:locations,id'],
            // Required, not nullable: assignments.position_id is NOT NULL at
            // the database level, and HireCandidateService falls back to the
            // requisition's position when the hire form doesn't override it —
            // a requisition without one would 500 the moment someone hires
            // from it instead of failing validation up front.
            'position_id' => ['required', 'exists:positions,id'],
            'hiring_manager_user_id' => ['nullable', 'exists:users,id'],
            'target_pay_range_min' => ['nullable', 'numeric', 'min:0'],
            'target_pay_range_max' => ['nullable', 'numeric', 'gte:target_pay_range_min'],
            'employment_type' => ['required', 'in:hourly,salaried'],
            'status' => ['nullable', 'in:draft,open,on_hold,filled,closed'],
        ];
    }
}
