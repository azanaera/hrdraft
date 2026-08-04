<?php

namespace App\Http\Controllers\Admin;

use App\Domain\TimeOff\Models\TimeOffPolicy;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TimeOffPolicyController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        return response()->json(['data' => TimeOffPolicy::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'applies_to' => ['required', 'in:hourly,salaried,all'],
            'accrual_method' => ['required', 'in:fixed_annual,per_pay_period,none'],
            'accrual_rate' => ['required', 'numeric', 'min:0'],
            'max_balance' => ['nullable', 'numeric', 'min:0'],
        ]);

        $policy = TimeOffPolicy::create($data + ['is_active' => true]);

        return response()->json(['data' => $policy], 201);
    }

    public function update(Request $request, TimeOffPolicy $timeOffPolicy)
    {
        abort_unless($request->user()->hasBackOfficeAccess(), 403);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'accrual_rate' => ['sometimes', 'numeric', 'min:0'],
            'max_balance' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $timeOffPolicy->update($data);

        return response()->json(['data' => $timeOffPolicy]);
    }
}
