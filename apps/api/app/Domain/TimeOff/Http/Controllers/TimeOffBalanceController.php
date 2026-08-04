<?php

namespace App\Domain\TimeOff\Http\Controllers;

use App\Domain\Employee\Models\Employment;
use App\Domain\TimeOff\Models\TimeOffBalance;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TimeOffBalanceController extends Controller
{
    public function index(Request $request, Employment $employment)
    {
        if ($request->user()->role === 'employee' && $request->user()->employment_id !== $employment->id) {
            throw new HttpException(403);
        }

        $balances = TimeOffBalance::where('employment_id', $employment->id)->with('policy')->get();

        return response()->json([
            'data' => $balances->map(fn ($b) => [
                'policy' => $b->policy->name,
                'policy_id' => $b->policy_id,
                'balance_hours' => (float) $b->balance_hours,
                'as_of_date' => $b->as_of_date?->toDateString(),
            ]),
        ]);
    }
}
